<?php

namespace Tests\Unit\Support;

use App\Support\NotificationMetadata;
use PHPUnit\Framework\TestCase;

class NotificationMetadataTest extends TestCase
{
    public function test_critical_case_defaults_to_critical_and_retryable_metadata(): void
    {
        $data = NotificationMetadata::enrich('critical_case', ['url' => '/admin/visits/1']);

        $this->assertSame('critical', $data['severity']);
        $this->assertTrue($data['retryable']);
        $this->assertSame('alarm', $data['sound_mode']);
        $this->assertSame('high', $data['web_urgency']);
        $this->assertSame('high', $data['android_message_priority']);
        $this->assertSame('/admin/visits/1', $data['url']);
    }

    public function test_birthday_defaults_to_medium_non_retryable_metadata(): void
    {
        $data = NotificationMetadata::enrich('birthday');

        $this->assertSame('medium', $data['severity']);
        $this->assertFalse($data['retryable']);
        $this->assertSame('soft', $data['sound_mode']);
        $this->assertSame('normal', $data['web_urgency']);
    }
}
