<?php

namespace App\Services\Inspections;

use App\Models\Inspection;
use App\Models\InspectionReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InspectionReportService
{
    private InspectionCompletenessValidator $completenessValidator;

    public function __construct(InspectionCompletenessValidator $completenessValidator)
    {
        $this->completenessValidator = $completenessValidator;
    }

    public function generateAndLock(Inspection $inspection): InspectionReport
    {
        $inspection->load(['vehicle.vehicle_brand', 'vehicle.vehicle_model', 'driver', 'photos', 'damages.photos', 'signatures', 'createdBy', 'responsibleUser']);
        $missingItems = $this->completenessValidator->getMissingItems($inspection);
        $checklist = $this->buildChecklistSummary($inspection);
        $photoSections = $this->buildPhotoSections($inspection);
        $damages = $this->buildDamagesSummary($inspection);
        $signatures = $this->buildSignaturesSummary($inspection);

        $pdf = Pdf::loadView('admin.inspections.pdf', [
            'inspection' => $inspection,
            'missingItems' => $missingItems,
            'checklist' => $checklist,
            'photoSections' => $photoSections,
            'damages' => $damages,
            'signatures' => $signatures,
        ])->setPaper('a4');

        $fileName = sprintf('inspections/reports/inspection_%d_%s.pdf', $inspection->id, now()->format('Ymd_His'));
        $content = $pdf->output();
        Storage::disk('public')->put($fileName, $content);

        $report = InspectionReport::updateOrCreate(
            ['inspection_id' => $inspection->id],
            [
                'pdf_path' => $fileName,
                'pdf_hash' => hash('sha256', $content),
                'snapshot_json' => $inspection->toArray(),
                'generated_at' => now(),
                'immutable' => true,
            ]
        );

        $inspection->update([
            'status' => 'closed',
            'completed_at' => now(),
            'locked_at' => now(),
            'current_step' => 12,
        ]);

        return $report;
    }

    private function buildChecklistSummary(Inspection $inspection): array
    {
        $items = [];
        foreach ($inspection->checkItems as $item) {
            if ($item->value_int !== null) {
                $items[$item->group_key][$item->item_key] = (int) $item->value_int;
                continue;
            }
            if ($item->value_text !== null) {
                $items[$item->group_key][$item->item_key] = (string) $item->value_text;
                continue;
            }
            $items[$item->group_key][$item->item_key] = (bool) $item->value_bool;
        }

        return $items;
    }

    private function buildPhotoSections(Inspection $inspection): array
    {
        $sections = [];
        $groups = $inspection->photos->groupBy(fn ($photo) => (string) $photo->category);

        foreach ($groups as $category => $photos) {
            $rows = [];
            foreach ($photos as $photo) {
                $rows[] = [
                    'label' => $this->photoLabel((string) $category, (string) ($photo->slot ?? '')),
                    'slot' => (string) ($photo->slot ?? '-'),
                    'thumb' => $this->storageImageToDataUri((string) $photo->path, 760, 66),
                ];
            }

            if (!empty($rows)) {
                $sections[] = [
                    'category' => (string) $category,
                    'category_label' => $this->categoryLabel((string) $category),
                    'items' => $rows,
                ];
            }
        }

        return $sections;
    }

    private function buildDamagesSummary(Inspection $inspection): array
    {
        $damageTypes = (array) config('inspections.damage_types', []);
        $damageLocations = (array) config('inspections.damage_locations', []);
        $damageParts = (array) config('inspections.damage_parts', []);

        return $inspection->damages->map(function ($damage) use ($damageTypes, $damageLocations, $damageParts) {
            $photos = [];
            foreach ($damage->photos as $photo) {
                $photos[] = $this->storageImageToDataUri((string) $photo->path, 760, 66);
            }

            $locationKey = (string) $damage->location;
            $pieceKey = (string) $damage->part;
            $pieceLabel = $damageParts[$locationKey]['sections'][$pieceKey] ?? $pieceKey;

            return [
                'id' => (int) $damage->id,
                'scope' => (string) $damage->scope,
                'location' => $damageLocations[$locationKey] ?? $locationKey,
                'part' => (string) $pieceLabel,
                'part_section' => (string) ($damage->part_section ?? ''),
                'damage_type' => $damageTypes[$damage->damage_type] ?? (string) $damage->damage_type,
                'notes' => (string) ($damage->notes ?? ''),
                'resolved' => (bool) $damage->is_resolved,
                'photos' => array_values(array_filter($photos)),
            ];
        })->values()->all();
    }

    private function buildSignaturesSummary(Inspection $inspection): array
    {
        return $inspection->signatures->map(function ($signature) {
            $path = (string) ($signature->signature_path ?? '');
            $isStoredImage = $path !== '' && !str_starts_with($path, 'typed-signature:');

            return [
                'role' => (string) $signature->role,
                'name' => (string) $signature->signed_by_name,
                'signed_at' => optional($signature->signed_at)->format('Y-m-d H:i:s'),
                'image' => $isStoredImage ? $this->storageImageToDataUri($path, 480, 72) : null,
            ];
        })->values()->all();
    }

    private function categoryLabel(string $category): string
    {
        return match ($category) {
            'document' => 'Documentacao',
            'exterior' => 'Fotos exteriores',
            'interior' => 'Fotos interiores',
            'extra' => 'Fotos extra',
            default => ucfirst(str_replace('_', ' ', $category)),
        };
    }

    private function photoLabel(string $category, string $slot): string
    {
        if ($slot === '') {
            return $this->categoryLabel($category);
        }

        if (str_starts_with($slot, 'doc_')) {
            $doc = substr($slot, 4);
            $labels = [
                'dua' => 'DUA',
                'insurance' => 'Seguro',
                'inspection_periodic' => 'Inspecao periodica',
                'tvde_stickers' => 'Disticos TVDE',
                'no_smoking_sticker' => 'Autocolante proibicao fumar',
                'fuel_energy' => 'Foto combustivel/energia',
                'odometer' => 'Foto odometro',
                'tires' => 'Fotos pneus',
                'panel_warning' => 'Foto avisos painel',
                'damage' => 'Foto dano',
            ];
            return $labels[$doc] ?? ucfirst(str_replace('_', ' ', $doc));
        }

        $ext = (array) config('inspections.slot_labels.exterior', []);
        $int = (array) config('inspections.slot_labels.interior', []);
        if (isset($ext[$slot])) {
            return (string) $ext[$slot];
        }
        if (isset($int[$slot])) {
            return (string) $int[$slot];
        }
        return ucfirst(str_replace('_', ' ', $slot));
    }

    private function storageImageToDataUri(string $path, int $maxDimension = 760, int $quality = 66): ?string
    {
        if ($path === '' || !Storage::disk('public')->exists($path)) {
            return null;
        }

        $binary = Storage::disk('public')->get($path);
        if ($binary === '') {
            return null;
        }

        $mime = $this->detectMime($binary, $path);
        if (!str_starts_with($mime, 'image/')) {
            return null;
        }

        if (!function_exists('imagecreatefromstring')) {
            return 'data:' . $mime . ';base64,' . base64_encode($binary);
        }

        $source = @imagecreatefromstring($binary);
        if (!$source) {
            return 'data:' . $mime . ';base64,' . base64_encode($binary);
        }

        $srcW = imagesx($source);
        $srcH = imagesy($source);
        if ($srcW < 1 || $srcH < 1) {
            imagedestroy($source);
            return null;
        }

        $scale = min(1, $maxDimension / max($srcW, $srcH));
        $dstW = max(1, (int) round($srcW * $scale));
        $dstH = max(1, (int) round($srcH * $scale));

        $dest = imagecreatetruecolor($dstW, $dstH);
        $bg = imagecolorallocate($dest, 255, 255, 255);
        imagefilledrectangle($dest, 0, 0, $dstW, $dstH, $bg);
        imagecopyresampled($dest, $source, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);

        ob_start();
        imagejpeg($dest, null, max(45, min(85, $quality)));
        $jpegData = (string) ob_get_clean();
        imagedestroy($dest);
        imagedestroy($source);

        if ($jpegData === '') {
            return 'data:' . $mime . ';base64,' . base64_encode($binary);
        }

        return 'data:image/jpeg;base64,' . base64_encode($jpegData);
    }

    private function detectMime(string $binary, string $path): string
    {
        if (function_exists('finfo_open')) {
            $f = finfo_open(FILEINFO_MIME_TYPE);
            if ($f) {
                $mime = finfo_buffer($f, $binary) ?: '';
                finfo_close($f);
                if ($mime !== '') {
                    return $mime;
                }
            }
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'image/jpeg',
        };
    }
}
