<?php

namespace Revolution\Mastodon;

use GuzzleHttp\ClientInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Psr\Http\Message\ResponseInterface;
use Revolution\Mastodon\Contracts\Factory;

class MastodonClient implements Factory
{
    use Concerns\Apps;
    use Concerns\Accounts;
    use Concerns\Medias;
    use Concerns\Statuses;
    use Concerns\Streaming;
    use Macroable;
    use Conditionable;

    protected string $api_version = 'v1';

    protected ?ClientInterface $client = null;

    protected string $domain = '';

    protected string $token = '';

    protected string $api_base = '/api/';

    protected ?ResponseInterface $response = null;

    public function call(string $method, string $api, array $options = []): array
    {
        $response = Http::baseUrl($this->apiEndpoint())
            ->when(isset($this->client), fn (PendingRequest $client) => $client->setClient($this->client))
            ->when(filled($this->token), fn (PendingRequest $client) => $client->withToken($this->token))
            ->when(isset($options['multipart']['file']), fn (PendingRequest $client) => $client->attach(
                'file',
                Psr7\Utils::tryFopen($options['multipart']['file'], 'r'), 
                basename($options['multipart']['file']),
                ['Content-Type' => mime_content_type($options['multipart']['file'])])
            )
            ->send($method, $api, $options);

        $this->response = $response->toPsrResponse();

        return $response->json() ?? [];
    }

    public function get(string $api, array $query = []): array
    {
        $options = [];

        if (! empty($query)) {
            $options['query'] = $query;
        }

        return $this->call('GET', $api, $options);
    }

    public function post(string $api, array $params = []): array
    {
        $options = [];

        if (! empty($params)) {
            if (! isset($params['file'])) {
                $options['form_params'] = $params;
            } else {
                $options['multipart'] = $params;
            }
        }

        return $this->call('POST', $api, $options);
    }

    public function apiEndpoint(): string
    {
        return $this->domain.$this->api_base.$this->api_version;
    }

    public function setClient(ClientInterface $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function domain(string $domain): static
    {
        $this->domain = trim($domain, '/');

        return $this;
    }

    public function token(#[\SensitiveParameter] string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function apiVersion(string $api_version): static
    {
        $this->api_version = $api_version;

        return $this;
    }

    public function apiBase(string $api_base): static
    {
        $this->api_base = $api_base;

        return $this;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }
}
