<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Document extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia, HasFactory;

    public $table = 'documents';

    protected $dates = [
        'citizen_card_expires_at',
        'tvde_driver_certificate_expires_at',
        'criminal_record_expires_at',
        'driving_license_expires_at',
        'passport_expires_at',
        'residence_permit_expires_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'driver_id',
        'citizen_card_expires_at',
        'tvde_driver_certificate_expires_at',
        'criminal_record_expires_at',
        'driving_license_expires_at',
        'passport_expires_at',
        'residence_permit_expires_at',
        'notes',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $appends = [
        'citizen_card',
        'tvde_driver_certificate',
        'criminal_record',
        'profile_picture',
        'driving_license',
        'iban',
        'address',
        'passport',
        'residence_permit',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    /*

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')->fit('crop', 50, 50);
        $this->addMediaConversion('preview')->fit('crop', 120, 120);
    }

    */

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function getCitizenCardAttribute()
    {
        return $this->getMedia('citizen_card');
    }

    public function getTvdeDriverCertificateAttribute()
    {
        return $this->getMedia('tvde_driver_certificate');
    }

    public function getCriminalRecordAttribute()
    {
        return $this->getMedia('criminal_record');
    }

    public function getProfilePictureAttribute()
    {
        return $this->getMedia('profile_picture');
    }

    public function getDrivingLicenseAttribute()
    {
        return $this->getMedia('driving_license');
    }

    public function getIbanAttribute()
    {
        return $this->getMedia('iban');
    }

    public function getAddressAttribute()
    {
        return $this->getMedia('address');
    }

    public function getPassportAttribute()
    {
        return $this->getMedia('passport');
    }

    public function getResidencePermitAttribute()
    {
        return $this->getMedia('residence_permit');
    }

    public function getCitizenCardExpiresAtAttribute($value)
    {
        return $this->formatExpiryDate($value);
    }

    public function setCitizenCardExpiresAtAttribute($value): void
    {
        $this->attributes['citizen_card_expires_at'] = $this->parseExpiryDate($value);
    }

    public function getTvdeDriverCertificateExpiresAtAttribute($value)
    {
        return $this->formatExpiryDate($value);
    }

    public function setTvdeDriverCertificateExpiresAtAttribute($value): void
    {
        $this->attributes['tvde_driver_certificate_expires_at'] = $this->parseExpiryDate($value);
    }

    public function getCriminalRecordExpiresAtAttribute($value)
    {
        return $this->formatExpiryDate($value);
    }

    public function setCriminalRecordExpiresAtAttribute($value): void
    {
        $this->attributes['criminal_record_expires_at'] = $this->parseExpiryDate($value);
    }

    public function getDrivingLicenseExpiresAtAttribute($value)
    {
        return $this->formatExpiryDate($value);
    }

    public function setDrivingLicenseExpiresAtAttribute($value): void
    {
        $this->attributes['driving_license_expires_at'] = $this->parseExpiryDate($value);
    }

    public function getPassportExpiresAtAttribute($value)
    {
        return $this->formatExpiryDate($value);
    }

    public function setPassportExpiresAtAttribute($value): void
    {
        $this->attributes['passport_expires_at'] = $this->parseExpiryDate($value);
    }

    public function getResidencePermitExpiresAtAttribute($value)
    {
        return $this->formatExpiryDate($value);
    }

    public function setResidencePermitExpiresAtAttribute($value): void
    {
        $this->attributes['residence_permit_expires_at'] = $this->parseExpiryDate($value);
    }

    private function formatExpiryDate($value): ?string
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    private function parseExpiryDate($value): ?string
    {
        return $value ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d') : null;
    }
}
