<?php
/**
 * Supabase Client & Data Provider
 * Integrates PostgREST API with fallback to local high-speed snapshots
 */

declare(strict_types=1);

namespace App\Services;

class SupabaseClient
{
    private string $url;
    private string $key;
    private int $timeout;

    public function __construct(?string $url = null, ?string $key = null, int $timeout = 5)
    {
        $this->url = rtrim($url ?? (getenv('SUPABASE_URL') ?: 'https://eavbzjgxbemsomdajevh.supabase.co'), '/');
        $this->key = $key ?? (getenv('SUPABASE_SERVICE_ROLE_KEY') ?: getenv('SUPABASE_ANON_KEY') ?: '');
        $this->timeout = $timeout;
    }

    public function isConfigured(): bool
    {
        return !empty($this->key);
    }

    /**
     * Perform GET query against Supabase PostgREST table
     */
    public function get(string $table, array $params = []): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $query = http_build_query($params);
        $endpoint = "{$this->url}/rest/v1/{$table}" . ($query ? "?{$query}" : '');

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => [
                "apikey: {$this->key}",
                "Authorization: Bearer {$this->key}",
                "Accept: application/json"
            ]
        ]);

        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code >= 200 && $code < 300 && is_string($res)) {
            return json_decode($res, true);
        }

        return null;
    }

    /**
     * Insert rows into table via Supabase PostgREST (supports upsert)
     */
    public function upsert(string $table, array $rows, string $onConflict = 'id'): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        $endpoint = "{$this->url}/rest/v1/{$table}?on_conflict={$onConflict}";
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POSTFIELDS => json_encode($rows, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => [
                "apikey: {$this->key}",
                "Authorization: Bearer {$this->key}",
                "Content-Type: application/json",
                "Prefer: resolution=merge-duplicates"
            ]
        ]);

        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($code >= 200 && $code < 300);
    }

    /**
     * Call Supabase RPC / Stored Procedure
     */
    public function rpc(string $functionName, array $params = []): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $endpoint = "{$this->url}/rest/v1/rpc/{$functionName}";
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_POSTFIELDS => json_encode($params, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => [
                "apikey: {$this->key}",
                "Authorization: Bearer {$this->key}",
                "Content-Type: application/json"
            ]
        ]);

        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code >= 200 && $code < 300 && is_string($res)) {
            return json_decode($res, true);
        }

        return null;
    }
}
