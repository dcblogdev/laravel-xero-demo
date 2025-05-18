You are an expert in PHP, Laravel, Pest, and Tailwind.

# General Guidelines

## PHP Code Style
- Use PHP v8.4 features. 
- Follow pint.json coding rules. 
- Enforce strict types and array shapes via PHPStan.
- Follow the Laravel coding style as defined in the [Laravel documentation](https://laravel.com/docs/master/contributions#coding-style).
- Use Laravel Pint for code formatting with the Laravel preset.
- Use strict typing with `declare(strict_types=1)` at the top of PHP files.
- Use proper type hints for method parameters and return types.
- Use PHPDoc blocks for properties, methods, and functions with proper type annotations.
- Always import classes instead of using fully qualified class names (FQCNs)
- All methods must have type hints and return types, including model scopes and relationships
- Use camelCase for variables and methods, PascalCase for classes, and snake_case for database fields.
- Keep methods small and focused on a single responsibility.
- Use meaningful variable and method names that describe their purpose.
- PHPDoc types should use generics for collections and arrays
- Delete .gitkeep when adding a file. 
- Stick to existing structure—no new folders. 
- Avoid DB::; use Model::query() only. 
- No dependency changes without approval.
- app/Actions - Use Actions pattern and naming verbs.

Example:
```php
public function store(CreateTodoRequest $request, CreateTodoAction $action)
{
    $user = $request->user();

    $action->handle($user, $request->validated());
}
```

Example:
```php
/**
* Get the user's orders.
*
* @return HasMany<Order>
*/
public function orders(): HasMany
{
  return $this->hasMany(Order::class);
}
```
  
## Static Analysis
- Maintain a high level of code quality with PHPStan (level 8).
- Fix static analysis issues before committing code.
- Use IDE helpers for better code completion and static analysis.

## General
- Follow the Laravel documentation for the latest best practices.
- Use Laravel's built-in features instead of reinventing the wheel.
- Use dependency injection instead of facades when possible for better testability.
- Use environment variables for configuration.
- Use Laravel's validation features for input validation in form requests, don't do inline validation unless using a Livewire class
- Use permissions checks via Spatie Permissions package (installed) features for access control.

## Database
- Use migrations for database schema changes.
- Use UUID as the primary key with a name of id
- for relations use foreignUuid
- don't use enum column prefer to store as string and use Enum classes
- Use factories and seeders for test data.
- Use Eloquent relationships to define relationships between models.
- Use annocations query scopes for common query patterns. Use laravel 12 scope attributes.
- Use database transactions for operations that need to be atomic.

## Security
- Use Laravel's authentication and authorization features.
- Validate all user input.
- Use CSRF protection for forms.
- Use Laravel's encryption and hashing features.
- Implement proper error handling and logging.

## Models
- Models must create a `newFactory` method when using `HasFactory`
- Models should use `/** @use HasFactory<ModelNameFactory> */` when using `use HasFactory;`
- Models relationships must include docblocks for @param and @return types including generics.
- Fillables must include `@var list<string>`

- Example:
```php
/** @use HasFactory<TodoFactory> */
use HasFactory;

/**
 * The attributes that are mass assignable.
 *
 * @var list<string>
 */
protected $fillable = [
    'title',
    'description',
    'completed',
];

protected static function newFactory(): TodoFactory
{
    return TodoFactory::new();
}
```
