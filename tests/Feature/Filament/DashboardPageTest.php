<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\AssetActivityChart;
use App\Filament\Widgets\AssetsNeedingAction;
use App\Filament\Widgets\AssetStatusChart;
use App\Filament\Widgets\StatsOverview;
use App\Models\User;
use Filament\Widgets\TableWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class DashboardPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_dashboard_opens_and_is_called_overview_of_assets(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin')
            ->assertOk()
            ->assertSee('Огляд майна')
            ->assertDontSee('Інфопанель');
    }

    public function test_dashboard_is_our_page_with_title_and_navigation_label(): void
    {
        $this->assertSame('Огляд майна', Dashboard::getNavigationLabel());
        $this->assertSame('Огляд майна', (new Dashboard)->getTitle());
    }

    public function test_widget_order_is_explicit(): void
    {
        $this->assertSame(
            [StatsOverview::class, AssetActivityChart::class, AssetStatusChart::class, AssetsNeedingAction::class],
            (new Dashboard)->getWidgets(),
        );
    }

    public function test_widget_sort_values_are_unique_and_follow_the_order(): void
    {
        $sorts = array_map(
            fn (string $widget): ?int => $widget::getSort(),
            (new Dashboard)->getWidgets(),
        );

        $this->assertSame([1, 2, 3, 4], $sorts);
    }

    public function test_columns_are_responsive(): void
    {
        $this->assertSame(
            ['default' => 1, 'md' => 2, 'xl' => 2],
            (new Dashboard)->getColumns(),
        );
    }

    public function test_dashboard_widgets_do_not_poll_the_server(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        foreach ((new Dashboard)->getWidgets() as $class) {
            $widget = Livewire::test($class)->instance();

            // Stats and chart widgets poll through the widget itself, table widgets through their table.
            $interval = $widget instanceof TableWidget
                ? $widget->getTable()->getPollingInterval()
                : (new ReflectionMethod($widget, 'getPollingInterval'))->invoke($widget);

            $this->assertNull($interval, "{$class} must not poll");
        }
    }

    public function test_every_dashboard_widget_is_visible_to_all_roles(): void
    {
        foreach ([User::factory()->admin(), User::factory()->viewer(), User::factory()->editor()] as $factory) {
            $this->actingAs($factory->create())
                ->get('/admin')
                ->assertOk();
        }
    }
}
