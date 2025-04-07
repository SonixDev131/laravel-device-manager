# Laravel + Vue + Inertia: TDD-First Development Standards

## Core Principles
- **TDD-first**: Write tests before implementation (only for backend)
- **PHP 8.4**: Use strict types and modern features everywhere
- **Thin Controllers**: Business logic in Action classes
- **90%+ Test Coverage**: Required for all new code

## Code Structure & Patterns

### Backend
```
app/
  Actions/     # Single-purpose verb-named classes 
  Http/
    Controllers/ # Slim controllers using Actions
    Requests/    # FormRequest validation
  Models/      # $guarded = [] instead of $fillable
```

- **PHP Standards**: 
  - `declare(strict_types=1)` in all files
  - PHP 8.4 features: typed props, match, readonly, constructor property promotion, first class callable syntax
  - `Model::query()` over `DB::` facade
  - DI over facades

- **Laravel Patterns**:
  - FormRequests for validation
  - Action classes for business logic
  - SOLID principles

### Frontend 
- **Vue 3.5**: `<script setup>` + Composition API
- **Component Strategy**: Shadcn/Vue with Tailwind
- **Form Handling**: Inertia `useForm()` + TypeScript interfaces
- **Navigation**: `router` for full-page, `axios` for API JSON

## TDD Workflow

1. Write Pest test first (AAA pattern)
2. Implement minimal code to pass test
3. Refactor while keeping tests green
4. Run `sail composer lint && sail composer test`
5. Verify 90%+ coverage

## Example Pattern

```php
// Test First (tests/Unit/Actions/CreateTodoActionTest.php)
test('creates a todo for user', function () {
    $user = User::factory()->create();
    
    $todo = (new CreateTodoAction())->handle($user, [
        'title' => 'Test Todo',
        'description' => 'Test description',
    ]);
    
    expect($todo)
        ->title->toBe('Test Todo')
        ->user_id->toBe($user->id);
});

// Implementation (app/Actions/CreateTodoAction.php)
declare(strict_types=1);
namespace App\Actions;
use App\Models\Todo;
use App\Models\User;

class CreateTodoAction
{
    public function handle(User $user, array $data): Todo
    {
        return $user->todos()->create($data);
    }
}

// Controller (app/Http/Controllers/TodoController.php) 
declare(strict_types=1);
namespace App\Http\Controllers;
use App\Actions\CreateTodoAction;
use App\Http\Requests\CreateTodoRequest;

class TodoController extends Controller
{
    public function store(CreateTodoRequest $request, CreateTodoAction $action)
    {
        $todo = $action->handle($request->user(), $request->validated());
        return redirect()->route('todos.show', $todo);
    }
}
```
