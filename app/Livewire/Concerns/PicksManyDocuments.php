<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Add Doc takes one document or several at once: the files picked (or
 * dropped) together arrive one after another on $docPick — Livewire's S3
 * temporary uploads take one file at a time — and each joins $docFiles, with
 * a title made from its file name in $docTitles (same keys) to be changed
 * before saving. Every file becomes a document of its own.
 *
 * The component uses WithFileUploads.
 */
trait PicksManyDocuments
{
    /** Each file as it arrives. */
    public $docPick = null;

    /** The files to add, in the order picked. */
    public array $docFiles = [];

    /** Each file's title, same keys as $docFiles. */
    public array $docTitles = [];

    /** Files that can be added in one go. */
    public static function maxDocs(): int
    {
        return 20;
    }

    /** Each file joins the ones before it — up to maxDocs(), each up to 5 MB. */
    public function updatedDocPick(): void
    {
        $this->resetErrorBag(['docPick', 'docFiles']);
        $file = $this->docPick;
        $this->docPick = null;

        if (!$file) {
            return;
        }

        $name = $file->getClientOriginalName();

        if (count($this->docFiles) >= static::maxDocs()) {
            $this->addError('docFiles', 'Up to ' . static::maxDocs() . ' documents can be added at once — ' . $name . ' was left out.');
            return;
        }

        $check = Validator::make(['doc' => $file], ['doc' => 'file|max:5120'], [
            'doc.max'  => ':name is larger than 5 MB.',
            'doc.file' => ':name could not be uploaded.',
        ]);

        if ($check->fails()) {
            $this->addError('docPick', str_replace(':name', $name, $check->errors()->first('doc')));
            return;
        }

        $this->docFiles[]  = $file;
        $this->docTitles[] = static::titleFromFileName($name);
    }

    public function removeDoc(int $index): void
    {
        unset($this->docFiles[$index], $this->docTitles[$index]);
        $this->docFiles  = array_values($this->docFiles);
        $this->docTitles = array_values($this->docTitles);
        $this->resetErrorBag(['docFiles', 'docPick']);
        $this->resetValidation('docTitles.*');
    }

    /** "summer_vacation-circular.pdf" → "Summer vacation circular" */
    public static function titleFromFileName(string $name): string
    {
        $title = pathinfo($name, PATHINFO_FILENAME);
        $title = trim(preg_replace('/\s+/', ' ', str_replace(['_', '-', '.'], ' ', $title)));

        return Str::limit(Str::ucfirst($title !== '' ? $title : $name), 255, '');
    }

    /** The rules and messages for the files being added. */
    protected function manyDocsRules(): array
    {
        return [
            'docFiles'    => 'required|array|min:1|max:' . static::maxDocs(),
            'docFiles.*'  => 'file|max:5120',
            'docTitles'   => 'array',
            'docTitles.*' => 'required|string|max:255',
        ];
    }

    protected function manyDocsMessages(): array
    {
        return [
            'docFiles.required'    => 'Choose at least one file to upload.',
            'docFiles.min'         => 'Choose at least one file to upload.',
            'docFiles.max'         => 'Up to ' . static::maxDocs() . ' documents can be added at once.',
            'docFiles.*.max'       => 'Each file may be up to 5 MB.',
            'docTitles.*.required' => 'Give this document a title.',
            'docTitles.*.max'      => 'Keep the title to 255 characters.',
        ];
    }

    /**
     * Put one picked file in $dir on S3 (public) and return its key — only
     * once it is really there: the s3 disk doesn't throw on a failed write
     * (and Livewire's store() hands back the key all the same), so a failed
     * upload would otherwise leave a document with no file behind it.
     */
    protected function storeDoc($file, string $dir): string
    {
        $disk = Storage::disk('s3');

        try {
            $key    = $file->store($dir, 's3');
            $stored = $key && $disk->exists($key);
        } catch (\Throwable $e) {
            report($e);
            $stored = false;
        }

        if (!$stored) {
            throw new \RuntimeException('the file could not be stored');
        }

        $disk->setVisibility($key, 'public');

        return $key;
    }

    /** Drop one added file from the list once its document is saved. */
    protected function forgetDoc(int $index): void
    {
        unset($this->docFiles[$index], $this->docTitles[$index]);
    }

    protected function resetManyDocs(): void
    {
        $this->docPick   = null;
        $this->docFiles  = [];
        $this->docTitles = [];
    }
}
