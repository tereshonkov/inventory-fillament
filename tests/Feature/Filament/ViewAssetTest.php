<?php

namespace Tests\Feature\Filament;

use App\Enums\AssetStatus;
use App\Filament\Resources\Assets\AssetResource;
use App\Models\Asset;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewAssetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function viewUrl(Asset $asset): string
    {
        return AssetResource::getUrl('view', ['record' => $asset]);
    }

    public function test_heading_and_subheading_identify_the_asset(): void
    {
        $asset = Asset::factory()->create(['name' => 'Ноутбук Dell Latitude', 'inventory_number' => '101234567']);

        $this->actingAs(User::factory()->admin()->create())
            ->get($this->viewUrl($asset))
            ->assertOk()
            ->assertSee('Ноутбук Dell Latitude')
            ->assertSee('№ 101234567');
    }

    public function test_long_multi_paragraph_name_is_shortened_in_heading_but_kept_in_full_on_the_card(): void
    {
        $name = "Один Два Три Чотири П'ять Шість\n\nДругий абзац назви\n\nТретій абзац назви";
        $asset = Asset::factory()->create(['name' => $name]);

        $html = $this->actingAs(User::factory()->admin()->create())
            ->get($this->viewUrl($asset))
            ->assertOk()
            ->getContent();

        preg_match('#<h1[^>]*>(.*?)</h1>#s', $html, $matches);
        $heading = trim(html_entity_decode(strip_tags($matches[1] ?? '')));

        $this->assertSame('Один Два Три Чотири…', $heading);
        // The full name is still available in the infolist ("Повна назва").
        $this->assertStringContainsString('Повна назва', $html);
        $this->assertStringContainsString('Третій абзац назви', $html);
    }

    public function test_year_is_shown_without_thousands_separator(): void
    {
        $asset = Asset::factory()->create(['year' => 2024]);

        $html = $this->actingAs(User::factory()->admin()->create())
            ->get($this->viewUrl($asset))
            ->assertOk()
            ->assertSee('2024')
            ->getContent();

        // The separator depends on the locale: space, NBSP, narrow NBSP, comma or dot.
        $this->assertDoesNotMatchRegularExpression('/2[\s\x{00A0}\x{202F},.]024/u', $html);
    }

    public function test_status_and_primary_facts_are_visible(): void
    {
        $custodian = Employee::factory()->create(['full_name' => 'Іваненко Іван']);
        $asset = Asset::factory()
            ->status(AssetStatus::REPAIR)
            ->custodian($custodian)
            ->create(['serial_number' => 'SN-VIEW-1']);

        $this->actingAs(User::factory()->admin()->create())
            ->get($this->viewUrl($asset))
            ->assertOk()
            ->assertSee(AssetStatus::REPAIR->getLabel())
            ->assertSee($asset->location->name)
            ->assertSee($asset->type->name)
            ->assertSee('Іваненко Іван')
            ->assertSee('SN-VIEW-1');
    }

    public function test_absent_facts_show_decision_relevant_placeholders(): void
    {
        $asset = Asset::factory()->create(['notes' => null, 'holder_id' => null]);

        $this->actingAs(User::factory()->admin()->create())
            ->get($this->viewUrl($asset))
            ->assertOk()
            ->assertSee('Немає приміток')
            ->assertSee('Не призначено');
    }

    public function test_service_information_section_is_present(): void
    {
        $asset = Asset::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get($this->viewUrl($asset))
            ->assertOk()
            ->assertSee('Службова інформація')
            ->assertSee('Створено')
            ->assertSee('Оновлено');
    }

    public function test_editor_can_open_own_asset_but_not_a_foreign_one(): void
    {
        $employee = Employee::factory()->create();
        $editor = User::factory()->editor($employee)->create();

        $own = Asset::factory()->custodian($employee)->create();
        $foreign = Asset::factory()->create();

        $this->actingAs($editor)->get($this->viewUrl($own))->assertOk();
        $this->actingAs($editor)->get($this->viewUrl($foreign))->assertNotFound();
    }

    public function test_viewer_can_open_an_asset(): void
    {
        $asset = Asset::factory()->create();

        $this->actingAs(User::factory()->viewer()->create())
            ->get($this->viewUrl($asset))
            ->assertOk();
    }
}
