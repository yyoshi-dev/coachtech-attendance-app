<?php

namespace Tests\Feature\User;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrentDateTimeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 項目: 日時取得機能
     * 内容: 現在の日時情報がUIと同じ形式で出力されている
     */
    public function test_displayed_date_and_time_match_with_current_datetime(): void
    {
        // 現在時刻を固定
        Carbon::setTestNow('2026-04-10 08:00:00');

        // ユーザーを作成
        $user = User::factory()->create();

        // 勤怠打刻画面を開く
        $response = $this->actingAs($user)
            ->get(route('attendance.index'));
        $response->assertOk();

        // 画面に表示される日時情報を確認
        $date = now()->isoFormat('YYYY年M月D日(ddd)');
        $time = now()->isoFormat('HH:mm');
        $response->assertSeeInOrder([$date, $time]);

        // 現在時刻の固定を解除
        Carbon::setTestNow();
    }
}
