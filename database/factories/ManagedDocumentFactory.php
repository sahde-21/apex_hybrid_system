<?php

namespace Database\Factories;

use App\Enums\DocumentCategory;
use App\Models\ManagedDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

/**
 * @extends Factory<ManagedDocument>
 */
class ManagedDocumentFactory extends Factory
{
    protected $model = ManagedDocument::class;

    public function definition(): array
    {
        $disk = config('documents.disk', 'local');
        $name = fake()->words(3, true);
        $path = 'documents/test/'.fake()->uuid().'.txt';
        Storage::disk($disk)->put($path, fake()->paragraph());

        return [
            'folder_id' => null,
            'name' => $name,
            'original_name' => str($name)->slug().'.txt',
            'disk' => $disk,
            'path' => $path,
            'mime_type' => 'text/plain',
            'size' => Storage::disk($disk)->size($path),
            'category' => DocumentCategory::General,
            'tags' => [],
            'version' => 1,
            'owner_id' => User::factory(),
            'created_by' => fn (array $attrs) => $attrs['owner_id'],
            'updated_by' => fn (array $attrs) => $attrs['owner_id'],
        ];
    }
}
