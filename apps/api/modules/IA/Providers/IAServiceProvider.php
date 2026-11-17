<?php

declare(strict_types=1);

namespace Modules\IA\Providers;

use Anthropic\Client;
use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;
use Modules\Auditoria\Contracts\AuditoriaLlm;
use Modules\IA\Clients\AnthropicLlmClient;
use Modules\IA\Clients\AuditingLlmClient;
use Modules\Shared\Providers\ModuleServiceProvider;
use RiskEngine\Contracts\LlmClient;
use RiskEngine\Support\HeuristicLlmClient;

final class IAServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'ia';
    }

    public function register(): void
    {
        parent::register();

        $this->app->bind(LlmClient::class, function (Application $app) {
            $driver = (string) config('ia.driver');

            $client = match ($driver) {
                'heuristico' => new HeuristicLlmClient(),
                'anthropic' => $this->anthropic(),
                default => throw new InvalidArgumentException("Driver de IA desconhecido: {$driver}"),
            };

            return new AuditingLlmClient($client, $app->make(AuditoriaLlm::class), $driver);
        });
    }

    private function anthropic(): AnthropicLlmClient
    {
        $config = config('ia.anthropic');

        if (empty($config['api_key'])) {
            throw new InvalidArgumentException('IA_DRIVER=anthropic exige ANTHROPIC_API_KEY.');
        }

        return new AnthropicLlmClient(
            new Client(apiKey: $config['api_key'], requestOptions: ['timeout' => $config['timeout']]),
            $config['model'],
            $config['effort'],
            $config['max_tokens'],
        );
    }
}
