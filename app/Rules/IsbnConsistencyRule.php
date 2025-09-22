<?php

namespace App\Rules;

use App\Models\Book;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IsbnConsistencyRule implements ValidationRule
{
    protected $title;
    protected $author;

    public function __construct($title, $author)
    {
        $this->title  = $title;
        $this->author = $author;
    }

    /**
     * Run the validation rule.
     * Determine if the validation rule passes.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $existing = Book::where('isbn', $value)->first();

        if ($existing) {
            // ISBN exists, must match title + author
            if ($existing->title !== $this->title || $existing->author !== $this->author) {
                $fail('The ISBN does not match the title and author provided.');
            }
        }

        
    }
}
