<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Achievement;
use App\Models\UserAchievement;
use App\Models\User;
use App\Services\RulesEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class AchievementTest extends TestCase
{
    use RefreshDatabase;

    public function test_achievement_model_fillable_attributes()
    {
        $achievement = Achievement::create([
            'achievement_key' => 'test_achievement',
            'title' => 'Test Achievement',
            'description' => 'This is a test achievement',
            'points_reward' => 100,
            'rarity' => 'common',
            'icon_url' => 'https://example.com/icon.png',
            'is_active' => true,
            'trigger_event' => 'test_event',
            'trigger_count' => 1,
            'conditions' => ['field' => 'test', 'operator' => 'is', 'value' => 'value'],
            'category' => 'test',
            'sort_order' => 1,
            'type' => 'standard',
        ]);

        $this->assertEquals('test_achievement', $achievement->achievement_key);
        $this->assertEquals('Test Achievement', $achievement->title);
        $this->assertEquals('This is a test achievement', $achievement->description);
        $this->assertEquals(100, $achievement->points_reward);
        $this->assertEquals('common', $achievement->rarity);
        $this->assertEquals('https://example.com/icon.png', $achievement->icon_url);
        $this->assertTrue($achievement->is_active);
        $this->assertEquals('test_event', $achievement->trigger_event);
        $this->assertEquals(1, $achievement->trigger_count);
        $this->assertIsArray($achievement->conditions);
        $this->assertEquals(['field' => 'test', 'operator' => 'is', 'value' => 'value'], $achievement->conditions);
        $this->assertEquals('test', $achievement->category);
        $this->assertEquals(1, $achievement->sort_order);
        $this->assertEquals('standard', $achievement->type);
    }

    public function test_achievement_model_casts()
    {
        $achievement = Achievement::create([
            'achievement_key' => 'test_casts',
            'title' => 'Test Casts',
            'description' => 'Test achievement for casting',
            'trigger_event' => 'test_event',
            'points_reward' => '100',
            'is_active' => '1',
            'trigger_count' => '5',
            'conditions' => ['test' => 'value'],
            'sort_order' => '10',
        ]);

        $this->assertIsInt($achievement->points_reward);
        $this->assertIsBool($achievement->is_active);
        $this->assertIsInt($achievement->trigger_count);
        $this->assertIsArray($achievement->conditions);
        $this->assertIsInt($achievement->sort_order);
    }

    public function test_achievement_model_key_accessor()
    {
        $achievement = Achievement::create([
            'achievement_key' => 'test_key',
            'title' => 'Test Key',
            'description' => 'Test key achievement',
            'trigger_event' => 'test_event',
        ]);

        $this->assertEquals('test_key', $achievement->key);
    }

    public function test_active_scope()
    {
        Achievement::create([
            'achievement_key' => 'active_achievement',
            'title' => 'Active Achievement',
            'description' => 'Active achievement',
            'trigger_event' => 'test_event',
            'is_active' => true,
        ]);

        Achievement::create([
            'achievement_key' => 'inactive_achievement',
            'title' => 'Inactive Achievement',
            'description' => 'Inactive achievement',
            'trigger_event' => 'test_event',
            'is_active' => false,
        ]);

        $activeAchievements = Achievement::active()->get();
        $this->assertCount(1, $activeAchievements);
        $this->assertEquals('active_achievement', $activeAchievements->first()->achievement_key);
    }

    public function test_by_category_scope()
    {
        Achievement::create([
            'achievement_key' => 'category_a',
            'title' => 'Category A',
            'description' => 'Category A achievement', 
            'trigger_event' => 'test_event',
            'category' => 'category_a',
        ]);

        Achievement::create([
            'achievement_key' => 'category_b',
            'title' => 'Category B',
            'description' => 'Category B achievement',
            'trigger_event' => 'test_event',
            'category' => 'category_b',
        ]);

        $categoryA = Achievement::byCategory('category_a')->get();
        $this->assertCount(1, $categoryA);
        $this->assertEquals('category_a', $categoryA->first()->achievement_key);
    }

    public function test_by_rarity_scope()
    {
        Achievement::create([
            'achievement_key' => 'rarity_common',
            'title' => 'Common Rarity',
            'description' => 'Common rarity achievement',
            'trigger_event' => 'test_event',
            'rarity' => 'common',
        ]);

        Achievement::create([
            'achievement_key' => 'rarity_rare',
            'title' => 'Rare Rarity',
            'description' => 'Rare rarity achievement',
            'trigger_event' => 'test_event',
            'rarity' => 'rare',
        ]);

        $commonRarity = Achievement::byRarity('common')->get();
        $this->assertCount(1, $commonRarity);
        $this->assertEquals('rarity_common', $commonRarity->first()->achievement_key);
    }

    public function test_meets_conditions_method_with_empty_conditions()
    {
        $achievement = Achievement::create([
            'achievement_key' => 'no_conditions',
            'title' => 'No Conditions',
            'description' => 'No conditions achievement',
            'trigger_event' => 'test_event',
            'conditions' => null,
        ]);

        $result = $achievement->meetsConditions(['test' => 'value']);

        $this->assertTrue($result);
    }

    public function test_meets_conditions_method_with_conditions()
    {
        $achievement = Achievement::create([
            'achievement_key' => 'with_conditions',
            'title' => 'With Conditions',
            'description' => 'With conditions achievement',
            'trigger_event' => 'test_event',
            'conditions' => [
                ['field' => 'user.level', 'operator' => '>', 'value' => 5],
                ['field' => 'game.score', 'operator' => '>=', 'value' => 1000]
            ],
        ]);

        $mockedRulesEngine = Mockery::mock(RulesEngineService::class)->makePartial();
        $mockedRulesEngine->shouldReceive('evaluate')
             ->with(
                 [
                     ['field' => 'user.level', 'operator' => '>', 'value' => 5],
                     ['field' => 'game.score', 'operator' => '>=', 'value' => 1000]
                 ],
                 [
                     'user' => ['level' => 10],
                     'game' => ['score' => 1500]
                 ]
             )
             ->andReturn(true);
        
        $this->app->instance(RulesEngineService::class, $mockedRulesEngine);

        $result = $achievement->meetsConditions([
            'user' => ['level' => 10],
            'game' => ['score' => 1500]
        ]);

        $this->assertTrue($result);
    }
}