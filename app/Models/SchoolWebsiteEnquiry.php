<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An enquiry submitted from one of a school's public website forms: its
 * type (the form it came from, TYPES), name and contact, a subject and a
 * message, and every field the visitor filled in as [{label, value}].
 */
class SchoolWebsiteEnquiry extends Model
{
    /** The website's forms, as the admin's Website Enquiries lists them: type => label. */
    public const TYPES = [
        'contact'     => 'Contact Us',
        'query'       => 'Query',
        'appointment' => 'Appointment',
        'career'      => 'Career',
        'admission'   => 'Online Admission',
    ];

    protected $fillable = [
        'organization_id',
        'type',
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /** The form it came from, by name ('Website' for one sent before forms had types). */
    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? 'Website';
    }

    /**
     * What the visitor filled in, as [label => value] in the form's order;
     * for one sent before forms kept their fields, its name, contact,
     * subject and message.
     */
    public function fields(): array
    {
        $out = [];
        foreach (is_array($this->data) ? $this->data : [] as $f) {
            $label = trim((string) ($f['label'] ?? ''));
            $value = trim((string) ($f['value'] ?? ''));
            if ($label !== '' && $value !== '') {
                $out[$label] = $value;
            }
        }

        return $out ?: array_filter([
            'Name'    => (string) $this->name,
            'Phone'   => (string) $this->phone,
            'Email'   => (string) $this->email,
            'Subject' => (string) $this->subject,
            'Message' => (string) $this->message,
        ], fn ($v) => trim($v) !== '');
    }
}
