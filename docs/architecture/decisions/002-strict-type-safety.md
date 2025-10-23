# ADR-002: Enforce Strict Type Safety

## Status

Accepted

## Date

2024-01-15

## Context

PHP historically allowed loose type juggling, which can lead to subtle bugs and runtime errors that are difficult to debug. With PHP 8.2+, strict typing and static analysis tools have matured significantly.

COPRRA handles financial transactions, product data, and user information where data integrity is critical. Type errors in production could lead to:
- Incorrect price calculations
- Data corruption
- Security vulnerabilities
- Poor developer experience

## Decision

We will enforce **strict type safety** across the entire codebase using:

1. **`declare(strict_types=1);`** at the top of every PHP file
2. **PHPStan at Level max** for static analysis
3. **Return type declarations** required for all methods
4. **Parameter type hints** required for all method parameters
5. **Property type hints** for all class properties (PHP 7.4+)
6. **Psalm** as a secondary static analysis tool with strict settings

No exceptions will be made except for legacy third-party code.

## Consequences

### Positive

- **Early error detection**: Type errors caught at static analysis time, not in production
- **Better IDE support**: Auto-completion, refactoring tools work more reliably
- **Self-documenting code**: Types serve as inline documentation
- **Safer refactoring**: Type checker prevents breaking changes
- **Production stability**: Fewer runtime type errors
- **Team productivity**: Developers catch mistakes before code review

### Negative

- **Increased verbosity**: More type annotations required
- **Learning curve**: Developers must understand PHP's type system deeply
- **Slower initial development**: Writing type-safe code takes slightly more time upfront
- **CI/CD time**: Static analysis adds ~30-60 seconds to pipeline
- **Third-party compatibility**: Some packages may not be type-safe, requiring stubs

### Neutral

- **Requires PHP 8.2+**: No support for older PHP versions
- **Continuous maintenance**: Type definitions must be kept up-to-date

## Alternatives Considered

### Alternative 1: Loose Typing (PHP Default)

**Pros**: Faster to write, fewer annotations, backward compatibility
**Cons**: Runtime errors, difficult debugging, poor IDE support, production bugs

**Reason not chosen**: Production reliability is more important than development speed. Type errors in financial calculations or user data handling are unacceptable.

### Alternative 2: Partial Typing (Types only in critical sections)

**Pros**: Balanced approach, flexibility where needed
**Cons**: Inconsistent codebase, difficult to determine what's "critical", slippery slope

**Reason not chosen**: Inconsistency leads to confusion. If types are valuable in critical code, they're valuable everywhere.

### Alternative 3: TypeScript + PHP Hybrid

**Pros**: Best type safety, full-stack type checking
**Cons**: Requires maintaining two type systems, team lacks TypeScript expertise

**Reason not chosen**: Team is PHP-focused. TypeScript adds complexity without sufficient benefit for a primarily server-rendered application.

## References

- [PHPStan Documentation](https://phpstan.org/)
- [Psalm Documentation](https://psalm.dev/)
- [PHP Type System RFC](https://wiki.php.net/rfc/scalar_type_hints_v5)
- Project phpstan.neon configuration (Level max)
- Project psalm.xml configuration
