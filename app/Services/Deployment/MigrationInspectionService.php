<?php

namespace App\Services\Deployment;

use Illuminate\Support\Facades\File;

class MigrationInspectionService
{
  /** @var list<string> */
  protected array $riskyKeywords = [
    'dropColumn',
    'dropIfExists',
    'drop(',
    'renameColumn',
    'rename(',
    'truncate',
    'delete(',
    'DB::statement',
    'Schema::drop',
  ];

  /**
   * @return array{pending: list<string>, applied: list<string>, risky: list<array{file: string, keywords: list<string>}>}
   */
  public function inspect(): array
  {
    $migrator = app('migrator');
    $files = $migrator->getMigrationFiles(database_path('migrations'));
    $ran = $migrator->getRepository()->getRan();

    $pending = array_values(array_diff(array_keys($files), $ran));
    $risky = [];

    foreach ($pending as $migration) {
      $path = $files[$migration];
      $contents = File::get($path);
      $matches = [];

      foreach ($this->riskyKeywords as $keyword) {
        if (str_contains($contents, $keyword)) {
          $matches[] = $keyword;
        }
      }

      if ($matches !== []) {
        $risky[] = [
          'file' => basename($path),
          'keywords' => $matches,
        ];
      }
    }

    return [
      'pending' => $pending,
      'applied' => $ran,
      'risky' => $risky,
    ];
  }
}
