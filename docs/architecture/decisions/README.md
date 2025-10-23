# Architecture Decision Records (ADRs)

This directory contains Architecture Decision Records (ADRs) for the COPRRA project, documenting significant architectural decisions made during the project's development.

## What is an ADR?

An Architecture Decision Record (ADR) is a document that captures an important architectural decision made along with its context and consequences.

## ADR Format

Each ADR follows this structure:

1. **Title**: Short descriptive title
2. **Status**: Proposed, Accepted, Deprecated, or Superseded
3. **Context**: The issue motivating this decision
4. **Decision**: The change being proposed or has been made
5. **Consequences**: The results of applying this decision (both positive and negative)

## Index of ADRs

| ADR | Title | Status | Date |
|-----|-------|--------|------|
| [ADR-001](001-use-laravel-framework.md) | Use Laravel Framework | Accepted | 2024-01-15 |
| [ADR-002](002-strict-type-safety.md) | Enforce Strict Type Safety | Accepted | 2024-01-15 |
| [ADR-003](003-service-layer-architecture.md) | Adopt Service Layer Architecture | Accepted | 2024-01-20 |
| [ADR-004](004-enum-based-state-management.md) | Use PHP 8.1+ Enums for State Management | Accepted | 2024-01-20 |
| [ADR-005](005-repository-pattern.md) | Implement Repository Pattern for Data Access | Accepted | 2024-01-25 |
| [ADR-006](006-multi-language-support.md) | Multi-Language and RTL Support Strategy | Accepted | 2024-02-01 |
| [ADR-007](007-store-adapter-pattern.md) | Store Adapter Pattern for External Integrations | Accepted | 2024-02-10 |
| [ADR-008](008-ai-service-integration.md) | OpenAI Integration for Product Classification | Accepted | 2024-03-01 |

## How to Create a New ADR

1. Copy the template from `template.md`
2. Number the file sequentially (e.g., `010-title.md`)
3. Fill in the sections with relevant information
4. Submit a pull request with the new ADR
5. Update this index after the ADR is accepted

## References

- [ADR GitHub Organization](https://adr.github.io/)
- [Documenting Architecture Decisions by Michael Nygard](http://thinkrelevance.com/blog/2011/11/15/documenting-architecture-decisions)
