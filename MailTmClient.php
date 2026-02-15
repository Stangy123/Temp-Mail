<?php
declare(strict_types=1);

final class MailTmClient
{
  private string $baseUrl;

  public function __construct(string $baseUrl)
  {
    $this->baseUrl = rtrim($baseUrl, '/');
  }

  /** @return array<string,mixed> */
  public function getDomains(int $page = 1): array
  {
    return $this->request('GET', "/domains?page={$page}");
  }

  /** @return array<string,mixed> */
  public function createAccount(string $address, string $password): array
  {
    return $this->request('POST', '/accounts', [
      'address' => $address,
      'password' => $password,
    ]);
  }

  /** @return array<string,mixed> */
  public function createToken(string $address, string $password): array
  {
    return $this->request('POST', '/token', [
      'address' => $address,
      'password' => $password,
    ]);
  }

  /** @return array<string,mixed> */
  public function listMessages(string $token, int $page = 1): array
  {
    return $this->request('GET', "/messages?page={$page}", null, $token);
  }

  /** @return array<string,mixed> */
  public function getMessage(string $token, string $id): array
  {
    $id = rawurlencode($id);
    return $this->request('GET', "/messages/{$id}", null, $token);
  }

  /** @return array<string,mixed> */
  private function request(string $method, string $path, ?array $json = null, ?string $bearerToken = null): array
  {
    $url = $this->baseUrl . $path;

    $ch = curl_init($url);
    if ($ch === false) {
      throw new RuntimeException('Failed to init cURL');
    }

    $headers = ['Accept: application/json'];
    if ($bearerToken) $headers[] = 'Authorization: Bearer ' . $bearerToken;

    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CUSTOMREQUEST  => $method,
      CURLOPT_HTTPHEADER     => $headers,
      CURLOPT_TIMEOUT        => 20,
    ]);

    if ($json !== null) {
      $payload = json_encode($json, JSON_UNESCAPED_SLASHES);
      if ($payload === false) {
        curl_close($ch);
        throw new RuntimeException('Failed to encode JSON');
      }
      $headers[] = 'Content-Type: application/json';
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }

    $raw = curl_exec($ch);
    if ($raw === false) {
      $err = curl_error($ch);
      curl_close($ch);
      throw new RuntimeException("cURL error: {$err}");
    }

    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($raw, true);
    if (!is_array($data)) $data = ['raw' => $raw];

    if ($status < 200 || $status >= 300) {
      $msg = $data['detail'] ?? $data['message'] ?? ('HTTP ' . $status);
      throw new RuntimeException("API error ({$status}): {$msg}");
    }

    return $data;
  }
}
