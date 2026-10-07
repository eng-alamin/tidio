<?php

namespace Tests\Unit;

use App\Services\Knowledge\HtmlTextExtractor;
use App\Services\Knowledge\PassageRanker;
use App\Services\Knowledge\RobotsRules;
use App\Services\Knowledge\UrlGuard;
use PHPUnit\Framework\TestCase;

class PassageRankerTest extends TestCase
{
    public function test_ranks_the_passage_that_answers_the_question_first(): void
    {
        $ranker = new PassageRanker;
        $passages = ['We ship worldwide with express delivery in two days.', 'Refunds are paid within five business days.', 'Our office is in Dhaka.'];

        $hits = $ranker->rank($passages, 'How long do refunds take?');

        $this->assertCount(1, $hits);
        $this->assertSame(1, $hits[0]['index']);
    }

    public function test_works_for_bengali(): void
    {
        $ranker = new PassageRanker;
        $passages = ['আমাদের দোকান সকাল ৯টা থেকে সন্ধ্যা ৬টা পর্যন্ত খোলা থাকে।', 'ডেলিভারি সারা দেশে করা হয়।'];

        $this->assertSame(0, $ranker->rank($passages, 'দোকান কখন খোলা থাকে')[0]['index']);
    }

    public function test_long_text_is_cut_into_passages_of_bounded_size(): void
    {
        $chunks = (new PassageRanker)->chunk(str_repeat('A sentence about widgets. ', 100), 300);

        $this->assertGreaterThan(5, count($chunks));
        $this->assertLessThanOrEqual(300, max(array_map('mb_strlen', $chunks)));
    }

    public function test_html_extractor_drops_scripts_menus_and_keeps_content(): void
    {
        $result = (new HtmlTextExtractor)->extract('<html><head><title>T</title></head><body><nav>MENU</nav><main><h1>Hi</h1><p>Body text.</p><script>x()</script></main><footer>FOOT</footer></body></html>', 'https://a.test/');

        $this->assertStringContainsString('Body text.', $result['text']);
        $this->assertStringNotContainsString('MENU', $result['text']);
        $this->assertStringNotContainsString('FOOT', $result['text']);
        $this->assertStringNotContainsString('x()', $result['text']);
    }

    public function test_robots_rules(): void
    {
        $rules = RobotsRules::parse("User-agent: *\nDisallow: /admin\nAllow: /admin/public\n");

        $this->assertFalse($rules->allows('https://a.test/admin/x'));
        $this->assertTrue($rules->allows('https://a.test/admin/public/x'));
        $this->assertTrue($rules->allows('https://a.test/blog'));
    }

    public function test_private_and_special_addresses_are_not_public(): void
    {
        $guard = new UrlGuard;

        foreach (['127.0.0.1', '10.0.0.1', '172.16.5.5', '192.168.1.1', '169.254.169.254', '100.64.0.1', '::1', '::ffff:127.0.0.1', 'fe80::1', 'fc00::1', '0.0.0.0'] as $ip) {
            $this->assertFalse($guard->isPublic($ip), $ip);
        }

        foreach (['8.8.8.8', '93.184.216.34', '2606:4700:4700::1111'] as $ip) {
            $this->assertTrue($guard->isPublic($ip), $ip);
        }
    }
}
