<?php

namespace Tests\Unit;

use App\Exceptions\InvalidPayloadException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Src\Infrastructure\Integrations\OnlyFans\OnlyFansProfileStatsParser;
use Tests\TestCase;

class OnlyFansProfileStatsParserTest extends TestCase
{
    private OnlyFansProfileStatsParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new OnlyFansProfileStatsParser;
    }

    #[Test]
    public function it_parses_the_legacy_top_level_likes_format(): void
    {
        $stats = $this->parser->parse([
            'likes' => 120000,
            'revision' => 10,
        ]);

        $this->assertSame(120000, $stats->likes);
        $this->assertSame(10, $stats->revision);
    }

    #[Test]
    public function it_parses_likes_nested_under_profile(): void
    {
        $stats = $this->parser->parse([
            'profile' => ['likes' => 121000],
            'revision' => 11,
        ]);

        $this->assertSame(121000, $stats->likes);
        $this->assertSame(11, $stats->revision);
    }

    #[Test]
    public function it_accepts_an_explicit_numeric_zero(): void
    {
        $stats = $this->parser->parse([
            'likes' => 0,
            'revision' => 0,
        ]);

        $this->assertSame(0, $stats->likes);
        $this->assertSame(0, $stats->revision);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[Test]
    #[DataProvider('invalidPayloads')]
    public function it_rejects_invalid_payloads(array $payload): void
    {
        $this->expectException(InvalidPayloadException::class);

        $this->parser->parse($payload);
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'missing likes' => [['revision' => 11]],
            'negative likes' => [['likes' => -1, 'revision' => 1]],
            'string likes' => [['likes' => 'many', 'revision' => 1]],
            'boolean likes' => [['likes' => true, 'revision' => 1]],
            'fractional likes' => [['likes' => 12.5, 'revision' => 1]],
            'missing revision' => [['likes' => 10]],
            'negative revision' => [['likes' => 1, 'revision' => -1]],
        ];
    }
}
