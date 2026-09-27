<?php

namespace Core;

/**
 * Client HTTP simple basé sur cURL pour PokeAPI.
 */
final class HttpClient
{
    public static function get(string $url, array $headers = []): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'PokemonGOManager/1.0',
            CURLOPT_HTTPHEADER => array_map(fn($k, $v) => "$k: $v", array_keys($headers), $headers),
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($body === false) {
            throw new \RuntimeException("HTTP error: $err");
        }
        return ['status' => $code, 'body' => $body];
    }

    public static function getJson(string $url, array $headers = []): array
    {
        $resp = self::get($url, $headers);
        if ($resp['status'] >= 400) {
            throw new \RuntimeException("HTTP {$resp['status']} pour $url");
        }
        return json_decode($resp['body'], true, 512, JSON_THROW_ON_ERROR);
    }
}
