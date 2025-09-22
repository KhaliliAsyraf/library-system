<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BorrowBook;
use App\Rules\IsbnConsistencyRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookController extends Controller
{
    public function __construct()
    {
        // Due to simple use cases, I put the logic inside controller
    }

    /**
     * To store new book
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Post(
     *     path="/api/books",
     *     summary="Add a new book",
     *     description="Store a new books",
     *     tags={"Books"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"isbn","title","author"},
     *             @OA\Property(property="isbn", type="integer", example=12345),
     *             @OA\Property(property="title", type="string", example="The Amazing Book"),
     *             @OA\Property(property="author", type="string", example="Jane Smith")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Books created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="isbn", type="integer", example="12345"),
     *             @OA\Property(property="title", type="string", example="The Amazing Book"),
     *             @OA\Property(property="author", type="string", example="Jane Smith")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The isbn field is required.")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'author' => 'required|string|max:255',
                'isbn' => ['required', 'integer', 'max_digits:13', new IsbnConsistencyRule($request->input('title'), $request->input('author'))],
            ]);
            
            $book = Book::create($validated);

            return response()->json($book, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error creating book: ' . $e->getMessage());
            return response()->json(['error' => 'Server Error'], 500);
        }
    }

    /**
     * To retrieve list of all books
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/api/books",
     *     summary="Get a paginated list of books",
     *     tags={"Books"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Pagination page number",
     *         required=false,
     *         @OA\Schema(type="integer", example="1")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="isbn", type="string", example="12345"),
     *                     @OA\Property(property="title", type="string", example="The Amazing Book"),
     *                     @OA\Property(property="author", type="string", example="Jane Smith"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-20T15:36:02.000000Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-20T15:36:02.000000Z")
     *                 )
     *             ),
     *             @OA\Property(property="first_page_url", type="string", example="http://localhost:8080/api/books?page=1"),
     *             @OA\Property(property="from", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=1),
     *             @OA\Property(property="last_page_url", type="string", example="http://localhost:8080/api/books?page=1"),
     *             @OA\Property(
     *                 property="links",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="url", type="string", nullable=true, example=null),
     *                     @OA\Property(property="label", type="string", example="« Previous"),
     *                     @OA\Property(property="page", type="integer", nullable=true, example=null),
     *                     @OA\Property(property="active", type="boolean", example=false)
     *                 )
     *             ),
     *             @OA\Property(property="next_page_url", type="string", nullable=true, example=null),
     *             @OA\Property(property="path", type="string", example="http://localhost:8080/api/books"),
     *             @OA\Property(property="per_page", type="integer", example=10),
     *             @OA\Property(property="prev_page_url", type="string", nullable=true, example=null),
     *             @OA\Property(property="to", type="integer", example=2),
     *             @OA\Property(property="total", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The isbn field is required.")
     *         )
     *     )
     * )
     */
    public function list(Request $request)
    {
        try {
            // Pagination to improve performance
            $books = Book::paginate(10);
            return response()->json($books, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching books: ' . $e->getMessage());
            return response()->json(['error' => 'Server Error'], 500);
        }
    }

    /**
     * To create new book borrow record
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Post(
     *     path="/api/books/borrow",
     *     summary="New borrow record",
     *     description="Store a new borrow record",
     *     tags={"Books"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"book_id","borrower_id"},
     *             @OA\Property(property="book_id", type="integer", example=1),
     *             @OA\Property(property="borrower_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Books borrowed successfully",
     *         @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Book borrowed successfully."),
     *         @OA\Property(
     *             property="data",
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="book_id", type="integer", example=12),
     *             @OA\Property(property="user_id", type="integer", example=5),
     *             @OA\Property(property="status", type="string", example="borrowed"),
     *             @OA\Property(property="borrowed_at", type="string", format="date-time", example="2025-09-20T12:34:56Z"),
     *             @OA\Property(property="returned_at", type="string", format="date-time", nullable=true, example=null),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-20T12:34:56Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-20T12:34:56Z")
     *         )
     *     )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="string", example="The book_id field is required.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Book is currently borrowed and not yet returned")
     *         )
     *     )
     * )
     */
    public function createBorrow(Request $request)
    {
        try {
            DB::beginTransaction();
            
            $validated = $request->validate([
                'book_id' => 'required|exists:books,id',
                'borrower_id' => 'required|exists:borrowers,id',
            ]);

            if (!$this->validToBorrow($validated['book_id'])) {
                return response()->json(['error' => 'Book is currently borrowed and not yet returned'], 400);
            }

            $borrow = BorrowBook::create(
                [
                    'book_id' => $validated['book_id'],
                    'borrower_id' => $validated['borrower_id'],
                    'borrowed_at' => now(),
                    'status' => 'borrowed'
                ]
            );

            DB::commit();

            return response()->json(
                [
                    'message' => 'Book borrowed successfully.',
                    'data' => $borrow
                ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating borrow record: ' . $e->getMessage());
            return response()->json(['error' => 'Server Error'], 500);
        }
    }

    /**
     * Returning borrowed book
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Put(
     *     path="/api/books/borrow/{id}/return",
     *     summary="Returning borrowed book",
     *     description="Returning borrowed book",
     *     tags={"Books"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Borrow ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Book returned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Book returned successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="string", example="The id field is required.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Book is already returned.")
     *         )
     *     )
     * )
     */
    public function returnBorrowed(Request $request, $id)
    {
        try {
            $request->merge(['id' => $id]);
            $request->validate([
                'id' => 'required|exists:borrow_books,id',
            ]);

            $borrowBook = BorrowBook::find($id);

            if ($this->bookHasReturned($id, $borrowBook)) {
                return response()->json(['error' => 'Book is already returned.'], 400);
            }

            $borrowBook->update([
                'returned_at' => now(),
                'status' => 'returned'
            ]);

            return response()->json(['message' => 'Book returned successfully.'], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error return book: ' . $e->getMessage());
            return response()->json(['error' => 'Server Error'], 500);
        }
    }

    /**
     * Check if the book is valid to borrow
     * 
     * @param int $bookId
     * @return bool
     */
    public function validToBorrow($bookId): bool
    {
        $status = BorrowBook::select('status')
            ->where('book_id', $bookId)
            ->latest('created_at')
            ->value('status');

        return is_null($status) || $status === 'returned';
    }

    /**
     * Check if the book has been returned
     * 
     * @param int $id
     * @return bool
     */
    public function bookHasReturned(int $id, ?BorrowBook $borrowBook = null): bool
    {
        if ($borrowBook) {
            return $borrowBook->status == 'returned';
        } else {
            return BorrowBook::where('book_id', $id)
            ->where('status', 'returned')
            ->latest('created_at')
            ->exists();
        }
    }
}
