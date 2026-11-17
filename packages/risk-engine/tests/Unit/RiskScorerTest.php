<?php

declare(strict_types=1);

namespace RiskEngine\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RiskEngine\Data\KnockOutSignal;
use RiskEngine\Enums\KnockOut;
use RiskEngine\Enums\RiskLevel;
use RiskEngine\Scoring\RiskScorer;
use RiskEngine\Scoring\ScoringPolicy;
use RiskEngine\Tests\Factories;

final class RiskScorerTest extends TestCase
{
    use Factories;

    public static function thresholds(): array
    {
        return [
            'baixo' => [20, RiskLevel::Low],
            'limite médio' => [35, RiskLevel::Medium],
            'médio' => [50, RiskLevel::Medium],
            'limite elevado' => [65, RiskLevel::High],
            'elevado' => [90, RiskLevel::High],
        ];
    }

    #[Test]
    #[DataProvider('thresholds')]
    public function classifica_pelo_score_ponderado(int $note, RiskLevel $expected): void
    {
        $scores = $this->scores(array_fill_keys(['financeira', 'tecnica', 'trabalhista', 'juridica', 'reputacional'], $note));

        $result = (new RiskScorer())->classify($scores, []);

        $this->assertSame($expected, $result['level']);
        $this->assertEqualsWithDelta($note, $result['score'], 0.001);
    }

    #[Test]
    public function aplica_os_pesos_por_dimensao(): void
    {
        // 100 * 0.30 (financeira) = 30
        $result = (new RiskScorer())->classify($this->scores(['financeira' => 100, 'tecnica' => 0, 'trabalhista' => 0, 'juridica' => 0, 'reputacional' => 0]), []);

        $this->assertEqualsWithDelta(30.0, $result['score'], 0.001);
    }

    #[Test]
    public function dimensao_critica_nao_e_diluida(): void
    {
        // score ponderado = 85*0.10 + 10*0.90 = 17.5 (baixo), mas reputacional ≥ 80
        $result = (new RiskScorer())->classify($this->scores(['reputacional' => 85]), []);

        $this->assertSame(RiskLevel::Medium, $result['level']);
    }

    #[Test]
    public function sinal_eliminatorio_forca_elevado(): void
    {
        $knockOut = new KnockOutSignal(KnockOut::SlaveLabor, 'd1', 'consta no cadastro');

        $result = (new RiskScorer())->classify($this->scores([]), [$knockOut]);

        $this->assertSame(RiskLevel::High, $result['level']);
        $this->assertStringContainsString('Lista Suja', implode(' ', $result['reasons']));
    }

    #[Test]
    public function politica_exige_pesos_que_somam_um(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ScoringPolicy(weights: ['financeira' => 0.5, 'tecnica' => 0.5, 'trabalhista' => 0.5, 'juridica' => 0, 'reputacional' => 0]);
    }
}
