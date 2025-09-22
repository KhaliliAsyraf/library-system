<?php

namespace App\Rules;

use App\Models\Book;
use App\Models\BorrowBook;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class BorrowBookExistsRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        Book::where('id', $value)->exists() ?: $fail('The selected book is invalid.');
        return;

        $status = BorrowBook::select('status')
            ->where('book_id', $value)
            ->latest('created_at')
            ->value('status');

        if (!is_null($status) || $status === 'returned') {
            $fail('The book is already returned.');
        }
    }
}
