<?php

namespace Tests\Feature\Servant;

use App\Livewire\Servant\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ServantProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_servant_can_upload_a_profile_photo_that_is_resized_and_stored(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        // A 1024x1024 image must come back as at most 512px and JPEG-encoded.
        $image = imagecreatetruecolor(1024, 1024);
        ob_start();
        imagejpeg($image, null, 90);
        $originalBinary = (string) ob_get_clean();
        imagedestroy($image);

        $component = Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('newPhoto', UploadedFile::fake()->createWithContent('me.jpg', $originalBinary))
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertNotNull($user->profile_photo);
        Storage::disk('public')->assertExists($user->profile_photo);

        $stored = imagecreatefromstring(Storage::disk('public')->get($user->profile_photo));
        $this->assertLessThanOrEqual(512, max(imagesx($stored), imagesy($stored)));
        imagedestroy($stored);
    }

    public function test_replacing_the_photo_deletes_the_previous_file(): void
    {
        Storage::fake('public');
        $user      = User::factory()->create();
        $firstPath = null;

        $component = Livewire::actingAs($user)->test(Profile::class);
        $component->set('newPhoto', UploadedFile::fake()->image('a.jpg', 400, 400));
        $firstPath = $user->refresh()->profile_photo;
        $this->assertNotNull($firstPath);

        $component->set('newPhoto', UploadedFile::fake()->image('b.jpg', 400, 400));

        Storage::disk('public')->assertMissing($firstPath);
        $this->assertNotSame($firstPath, $user->refresh()->profile_photo);
    }

    public function test_removing_the_photo_deletes_the_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('newPhoto', UploadedFile::fake()->image('me.jpg', 300, 300));

        $path = $user->refresh()->profile_photo;

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->call('removePhoto')
            ->assertDispatched('toast');

        Storage::disk('public')->assertMissing($path);
        $this->assertNull($user->refresh()->profile_photo);
    }

    public function test_oversized_photo_is_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('newPhoto', UploadedFile::fake()->create('big.jpg', 3000, 'image/jpeg'))
            ->assertHasErrors('newPhoto');

        $this->assertNull($user->refresh()->profile_photo);
    }
}
