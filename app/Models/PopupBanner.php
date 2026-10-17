<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PopupBanner extends Model
{
    use HasFactory;

    public const PAGE_LABELS = [
        'home'    => 'Beranda',
        'program' => 'Detail Program',
        'agent'   => 'Halaman Agen',
    ];

    public const TRIGGER_LABELS = [
        'welcome' => 'Saat halaman dibuka',
        'exit'    => 'Saat pengunjung akan keluar',
        'scroll'  => 'Saat mencapai akhir halaman',
        'time'    => 'Setelah beberapa menit',
    ];

    public const DAY_LABELS = [
        0 => 'Minggu',
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
    ];

    protected $fillable = [
        'title',
        'image',
        'url',
        'schedule_type',
        'start_date',
        'end_date',
        'days',
        'pages',
        'agent_scope',
        'agent_ids',
        'trigger_type',
        'delay_minutes',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'days'          => 'array',
        'pages'         => 'array',
        'agent_ids'     => 'array',
        'start_date'    => 'date',
        'end_date'      => 'date',
        'delay_minutes' => 'integer',
        'sort_order'    => 'integer',
        'is_active'     => 'boolean',
    ];

    public function getImageUrlAttribute()
    {
        return asset_photo_url($this->image);
    }

    public function getScheduleLabelAttribute()
    {
        if ($this->schedule_type === 'range') {
            $start = $this->start_date ? $this->start_date->format('d/m/Y') : '—';
            $end = $this->end_date ? $this->end_date->format('d/m/Y') : '—';

            return $start . ' s.d. ' . $end;
        }

        if ($this->schedule_type === 'days') {
            $days = array_map('intval', is_array($this->days) ? $this->days : []);
            $labels = array_map(function ($day) {
                return self::DAY_LABELS[$day] ?? '';
            }, $days);

            return $labels ? implode(', ', array_filter($labels)) : '—';
        }

        return 'Selalu tampil';
    }

    public function getPagesLabelAttribute()
    {
        $pages = is_array($this->pages) ? $this->pages : [];
        $labels = array_map(function ($page) {
            return self::PAGE_LABELS[$page] ?? $page;
        }, $pages);

        return $labels ? implode(', ', $labels) : 'Semua halaman';
    }

    public function getAgentScopeLabelAttribute()
    {
        if ($this->agent_scope !== 'selected') {
            return 'Semua agen';
        }

        $count = is_array($this->agent_ids) ? count($this->agent_ids) : 0;

        return $count . ' agen dipilih';
    }

    public function getTriggerLabelAttribute()
    {
        $label = self::TRIGGER_LABELS[$this->trigger_type] ?? $this->trigger_type;

        if ($this->trigger_type === 'time') {
            $label .= ' (' . max(1, (int) $this->delay_minutes) . ' menit)';
        }

        return $label;
    }
}
