# You are an expert in PHP, Laravel, Vue, Inertia, Pest, and Tailwind.

## 1. Language & Framework Standards

### PHP (v8.4)
- Utilize PHP 8.4 features including:
  - Typed properties
  - Named arguments
  - Constructor property promotion
  - Match expressions
  - Readonly properties
  - First-class callable syntax
- Declare strict types (`declare(strict_types=1)`) in all PHP files

### Laravel (v12.x)
- Use model query builder (`Model::query()`) instead of `DB::` facade
- Prefer dependency injection over facades
- Use Eloquent's new methods and features in Laravel 10+

### Vue (v3.5) with InertiaJS
- Use Composition API with `<script setup>` syntax
- Utilize new Vue 3.5 features:
  - `defineModel()` for two-way binding
  - Improved TypeScript support
  - Performance optimizations
- Follow InertiaJS best practices for page components and form handling
  - Use Inertia's shortcut request methods
    ```js
    import { router } from '@inertiajs/vue3'

    router.get(url, data, options)
    router.post(url, data, options)
    router.put(url, data, options)
    router.patch(url, data, options)
    router.delete(url, options)
    router.reload(options) // Uses the current URL
    ```


## 2. Project Structure

### Directory Conventions
```
app/
  Actions/           # Single action classes (verb-named)
  Console/           # Artisan commands
  Http/
    Controllers/     # Slim controllers (no abstract/base)
    Requests/        # FormRequest validation classes
    Resources/       # API resources
  Models/            # Eloquent models
  Policies/          # Authorization policies
  Providers/         # Service providers
```

### Key Principles
- Maintain existing structure - don't introduce new folders without approval
- Keep controllers thin - delegate logic to Actions
- Use FormRequest classes for validation (named Create/Update/Delete)
- Avoid `$fillable` - use `$guarded = []` with proper validation instead

## 3. Testing Standards

### Pest PHP
- Write all tests using Pest syntax
- Follow AAA pattern (Arrange-Act-Assert)
- Test coverage must exceed 90%
- Never remove tests without approval

### Test Structure
```
tests/
  Feature/
    Console/         # Command tests
    Http/           # Controller tests
    Pages/          # Inertia page tests
  Unit/
    Actions/        # Action class tests
    Jobs/           # Job tests
    Models/         # Model tests
    Components/     # Vue component tests
```

### Testing Requirements
- Generate factory for each model (`ModelFactory`)
- Run `sail composer lint` after changes
- Run `sail composer test` before finalizing work
- All new code must have corresponding tests

## 4. Frontend Standards

### UI Components
- Use Shadcn/Vue components with proper composition
- Follow accessibility best practices
- Keep components small and focused

### Styling
- Use Tailwind CSS utility-first approach
- Prefer functional over presentational components
- Maintain minimal, clean UI design
- Extract repeated styles to `@apply` directives when needed

## 5. Development Workflow

### Task Completion Checklist
1. Write tests for new functionality
2. Implement feature following standards
3. Run static analysis (`sail composer lint`)
4. Run tests (`sail composer test`)
5. Recompile assets if frontend changes were made
6. Verify all coding standards are met
7. Remove any temporary files (including `.gitkeep`)

### Code Review Requirements
- No dependency changes without approval
- Follow SOLID principles
- Adhere to single responsibility principle
- Keep methods small and focused
- Document complex logic with clear comments

## Example Implementation

### Action Class
```php
// app/Actions/CreateTodoAction.php
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
```

### Controller
```php
// app/Http/Controllers/TodoController.php
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

### FormRequest
```php
// app/Http/Requests/CreateTodoRequest.php
declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTodoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date', 'after:today'],
        ];
    }
}
```

### Pest Test
```php
// tests/Unit/Actions/CreateTodoActionTest.php
use App\Actions\CreateTodoAction;
use App\Models\User;

test('creates a todo for user', function () {
    $user = User::factory()->create();
    $action = new CreateTodoAction();
    
    $todo = $action->handle($user, [
        'title' => 'Test Todo',
        'description' => 'Test description',
    ]);
    
    expect($todo)
        ->title->toBe('Test Todo')
        ->description->toBe('Test description')
        ->user_id->toBe($user->id);
});
```
