<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BorrowBook extends Model
{
    protected $fillable = [
        'book_id',
        'borrower_id',
        'borrowed_at',
        'returned_at',
        'status'
    ];

    protected $casts = [
        'book_id' => 'integer',
        'borrower_id' => 'integer',
        'borrowed_at' => 'datetime',
        'returned_at' => 'datetime',
        'status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the book that owns the borrow record.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Get the borrower that owns the borrow record.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function borrower(): BelongsTo
    {
        return $this->belongsTo(Borrower::class);
    }
}
