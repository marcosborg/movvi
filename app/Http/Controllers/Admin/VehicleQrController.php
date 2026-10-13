<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VehicleItem;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Gate;
use Symfony\Component\HttpFoundation\Response;

class VehicleQrController extends Controller
{
    public function show(VehicleItem $vehicle)
    {
        abort_if(Gate::denies('vehicle_item_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $url = url('/app/vehicle/' . $vehicle->qr_token);
        $qrCode = QrCode::create($url)->setEncoding(new Encoding('UTF-8'))->setSize(360)->setMargin(20);
        $svg = (new SvgWriter)->write($qrCode)->getString();

        return view('admin.vehicleItems.qr', compact('vehicle', 'url', 'svg'));
    }
}
