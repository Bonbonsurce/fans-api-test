<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Src\Infrastructure\Integrations\OnlyFans\OnlyFansProfileParser;
use Tests\TestCase;

class OnlyFansProfileParserTest extends TestCase
{
    #[Test]
    public function deprecated_parser_only_reads_top_level_likes(): void
    {
        $parser = new OnlyFansProfileParser;

        $legacy = $parser->parse([
            'likes' => 120000,
            'revision' => 10,
        ]);
        $this->assertSame(120000, $legacy->likes);

        $nested = $parser->parse([
            'profile' => ['likes' => 121000],
            'revision' => 11,
        ]);
        $this->assertSame(0, $nested->likes);
        $this->assertSame(11, $nested->revision);
    }
}
