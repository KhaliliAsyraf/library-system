<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\Book;
use App\Models\Borrower;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BorrowBookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        putenv('API_BEARER_TOKEN=test-token');

        $this->withHeaders([
            'Authorization' => 'Bearer test-token',
            'Accept' => 'application/json',
        ]);
    }

    public function test_store_a_new_book_and_borrower_success()
    {
        // Arrange book data
        $bookData = [
            'isbn'   => '1234567890',
            'title'  => 'The Amazing Book',
            'author' => 'Jane Smith',
        ];

        // Act: create a book via API
        $bookResponse = $this->postJson(route('api.book.store'), $bookData);

        // Assert book was created
        $bookResponse->assertStatus(201)
                     ->assertJsonFragment([
                         'title'  => 'The Amazing Book',
                         'author' => 'Jane Smith',
                     ]);

        $book = Book::first();
        $this->assertNotNull($book);
        $this->assertEquals('1234567890', $book->isbn);

        // Arrange borrower data
        $borrowerData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        // Act: create a borrower via API
        $borrowerResponse = $this->postJson(route('api.borrower.store'), $borrowerData);

        // Assert borrower was created
        $borrowerResponse->assertStatus(201)
            ->assertJsonFragment([
                'name' => $borrowerData['name'],
                'email' => $borrowerData['email'],
            ]);

        // DB check
        $this->assertDatabaseHas('books', [
            'title' => 'The Amazing Book',
            'isbn'  => '1234567890',
            'author'=> 'Jane Smith',
        ]);

        $this->assertDatabaseHas('borrowers', [
            'name' => $borrowerData['name'],
        ]);
    }

    public function test_store_book_with_invalid_isbn_author_title()
    {
        $book = Book::factory()->create();
        $bookData = [
            'isbn'   => $book->isbn,
            'title'  => 'xxblablaxx', // Different title
            'author' => 'Jane Smith',
        ];

        $bookResponse = $this->postJson(route('api.book.store'), $bookData);
        $bookResponse->assertStatus(422)
                     ->assertJsonValidationErrors(['isbn'])
                     ->assertJsonFragment([
                        'isbn' => ['The ISBN does not match the title and author provided.'],
                    ]);
    }

    public function test_store_book_with_valid_isbn_author_title()
    {
        $book = Book::factory()->create();
        $bookData = [
            'isbn'   => $book->isbn,
            'title'  => $book->title,
            'author' => $book->author,
        ];

        $bookResponse = $this->postJson(route('api.book.store'), $bookData);
        $bookResponse->assertStatus(201)
                ->assertJsonFragment([
                'isbn' => $book->isbn,
                'title' => $book->title,
                'author' => $book->author,
            ]);

        $this->assertDatabaseHas('books', [
                'id' => $book->id,
            ]);
    }

    public function test_store_book_with_invalid_parameters()
    {
        $book = Book::factory()->create();
        $bookData = [
            'isbn'   => $book->isbn,
            'title'  => null,
            'author' => 123,
        ];

        $bookResponse = $this->postJson(route('api.book.store'), $bookData);
        $bookResponse->assertStatus(422)
                ->assertJsonFragment([
                'isbn' => ["The ISBN does not match the title and author provided."],
                'title' => ["The title field is required."],
                'author' => ["The author field must be a string."],
            ]);
    }

    public function test_borrow_book_success()
    {
        $book = Book::factory()->create();
        $borrower = Borrower::factory()->create();

        $borrowData = [
            'book_id'     => $book->id,
            'borrower_id' => $borrower->id,
        ];

        $borrowResponse = $this->postJson(route('api.book.borrow.create'), $borrowData);
        $borrowResponseData = $borrowResponse->json();
        $borrowResponse->assertStatus(201)
            ->assertJsonFragment([
                'id' => $borrowResponseData['data']['id'],
                'book_id' => $book->id,
                'borrower_id' => $borrower->id,
                'status' => 'borrowed',
            ]);

        $this->assertDatabaseHas('borrow_books', [
            'book_id' => $book->id,
            'borrower_id' => $borrower->id,
            'status' => 'borrowed',
        ]);
    }

    public function test_borrow_book_with_invalid_params()
    {
        $book = Book::factory()->create();
        $borrower = Borrower::factory()->create();

        $borrowData = [
            'book_id'     => $book->id,
            'borrower_id' => 123,
        ];

        $borrowResponse = $this->postJson(route('api.book.borrow.create'), $borrowData);
        $borrowResponseData = $borrowResponse->json();
        $borrowResponse->assertStatus(422)
            ->assertJsonFragment([
                'borrower_id' => ["The selected borrower id is invalid."],
            ]);
    }

    public function test_borrow_book_fail_with_status_remain_borrowed()
    {
        $book = Book::factory()->create();
        $firstBorrower = Borrower::factory()->create();

        $borrowData = [
            'book_id'     => $book->id,
            'borrower_id' => $firstBorrower->id,
        ];

        $this->postJson(route('api.book.borrow.create'), $borrowData);

        // --- 2nd borrow attempt should fail with same borrowed book ---
        $secondBorrower = Borrower::factory()->create();

        $borrowData = [
            'book_id'     => $book->id,
            'borrower_id' => $secondBorrower->id,
        ];

        $borrowResponse = $this->postJson(route('api.book.borrow.create'), $borrowData);
        $borrowResponse->assertStatus(400)
            ->assertJsonFragment([
                'error' => "Book is currently borrowed and not yet returned",
            ]);
    }

    public function test_return_borrowed_book_success()
    {
        $book = Book::factory()->create();
        $firstBorrower = Borrower::factory()->create();

        $borrowData = [
            'book_id'     => $book->id,
            'borrower_id' => $firstBorrower->id,
        ];

        $borrowResponse = $this->postJson(route('api.book.borrow.create'), $borrowData);
        $borrowResponseData = $borrowResponse->json();

        $returnResponse = $this->putJson(route('api.book.borrow.return', ['id' => $borrowResponseData['data']['id']]));
        $returnResponse->assertStatus(201)
            ->assertJsonFragment([
                'message' => 'Book returned successfully.'
            ]);

        $this->assertDatabaseHas('borrow_books', [
            'id' => $borrowResponseData['data']['id'],
            'status' => 'returned',
        ]);
    }

    public function test_return_borrowed_book_with_already_returned_status()
    {
        $book = Book::factory()->create();
        $firstBorrower = Borrower::factory()->create();

        $borrowData = [
            'book_id'     => $book->id,
            'borrower_id' => $firstBorrower->id,
        ];

        $borrowResponse = $this->postJson(route('api.book.borrow.create'), $borrowData);
        $borrowResponseData = $borrowResponse->json();

        $this->putJson(route('api.book.borrow.return', ['id' => $borrowResponseData['data']['id']]));

        $returnResponse = $this->putJson(route('api.book.borrow.return', ['id' => $borrowResponseData['data']['id']]));
        $returnResponse->assertStatus(400)
            ->assertJsonFragment([
                'error' => 'Book is already returned.'
            ]);
    }
}
