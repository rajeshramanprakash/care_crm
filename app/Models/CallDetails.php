<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'call_for', // lead, operation_lead, job_request
        'executive_id',
        'caller_id_number',
        'agent_number',
        'agent_name',
        'call_status',
        'recording_url',
        'call_received_datetime',
        'customer_name',
        'lead_code',
        'is_processed',
        'call_duration',
        'call_notes',
        'call_type',
        'call_source',
        'raw_data'
    ];

    protected $casts = [
        'call_received_datetime' => 'datetime',
        'is_processed' => 'boolean',
        'raw_data' => 'array'
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function executive()
    {
        return $this->belongsTo(User::class, 'executive_id');
    }

    /**
     * Match raw_data.call_id without mixing JSON string collation vs bound parameter collation (MySQL 1267).
     */
    public static function applyWhereRawDataCallIdEquals(Builder $query, string $tataCallId): Builder
    {
        $driver = (new static)->getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $query->whereRaw(
                'CAST(JSON_UNQUOTE(JSON_EXTRACT(raw_data, \'$.call_id\')) AS BINARY) = CAST(? AS BINARY)',
                [$tataCallId]
            );
        } else {
            $query->where('raw_data->call_id', $tataCallId);
        }

        return $query;
    }

    /**
     * tata_dialplan rows where raw_data.call_id matches (MySQL JSON + Laravel JSON path fallback).
     */
    public static function queryTataDialplanByRawCallId(string $tataCallId): Builder
    {
        $query = static::query()->where('call_source', 'tata_dialplan');

        return static::applyWhereRawDataCallIdEquals($query, $tataCallId);
    }

    /**
     * Webhook / final update: match dialplan row by Tata call_id (any call_status).
     */
    public static function findTataDialplanByTataCallId(string $tataCallId): ?self
    {
        return static::queryTataDialplanByRawCallId($tataCallId)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Dialplan IVR: update the ringing row for this leg. Prefer Tata call_id; fallback caller + latest ringing.
     */
    public static function findTataDialplanRingingForUpdate(?string $tataCallId, string $callerIdNumber): ?self
    {
        $tataCallId = $tataCallId !== null ? trim($tataCallId) : '';
        if ($tataCallId !== '') {
            $row = static::queryTataDialplanByRawCallId($tataCallId)
                ->where('call_status', 'ringing')
                ->orderByDesc('id')
                ->first();
            if ($row) {
                return $row;
            }
        }

        return static::query()
            ->where('call_source', 'tata_dialplan')
            ->where('call_status', 'ringing')
            ->where('caller_id_number', $callerIdNumber)
            ->orderByDesc('call_received_datetime')
            ->orderByDesc('id')
            ->first();
    }
}
