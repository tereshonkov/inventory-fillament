<?php

namespace Tests\Unit;

use App\Models\Asset;
use PHPUnit\Framework\TestCase;

class AssetShortNameTest extends TestCase
{
    public function test_short_name_keeps_names_of_up_to_four_words_unchanged(): void
    {
        $this->assertSame('E2E Чужий repair', (new Asset(['name' => 'E2E Чужий repair']))->short_name);
        $this->assertSame('Один Два Три Чотири', (new Asset(['name' => 'Один Два Три Чотири']))->short_name);
    }

    public function test_short_name_cuts_longer_names_to_four_words(): void
    {
        $asset = new Asset(['name' => 'Один Два Три Чотири П\'ять Шість']);

        $this->assertSame('Один Два Три Чотири…', $asset->short_name);
    }

    public function test_short_name_collapses_paragraphs_into_one_line(): void
    {
        $asset = new Asset(['name' => "Перший абзац назви\n\nДругий абзац назви\n\nТретій абзац назви"]);

        $this->assertSame('Перший абзац назви Другий…', $asset->short_name);
        $this->assertStringNotContainsString("\n", $asset->short_name);
    }

    public function test_short_name_guards_against_one_extremely_long_word(): void
    {
        $asset = new Asset(['name' => str_repeat('Ж', 500)]);

        $this->assertLessThanOrEqual(61, mb_strlen($asset->short_name));
        $this->assertStringEndsWith('…', $asset->short_name);
    }

    public function test_full_name_is_not_modified(): void
    {
        $name = "Один Два Три Чотири П'ять\n\nДругий абзац";

        $this->assertSame($name, (new Asset(['name' => $name]))->name);
    }
}
