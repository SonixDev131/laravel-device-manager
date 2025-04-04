---
name: Feature Request
about: Create a new feature with TDD-first approach
title: '[FEATURE] '
labels: 'feature'
assignees: ''

---

## User Story
As a [user role], I want to [feature/action], so that [benefit/value].

## Acceptance Criteria
- [ ] Criterion 1
- [ ] Criterion 2
- [ ] Criterion 3

## Technical Requirements

### Test Coverage (TDD-First)
- [ ] Unit test for Action class (`tests/Unit/Actions/...`)
- [ ] Feature test for controller endpoint (`tests/Feature/...`)
- [ ] Test edge cases and validation scenarios

### Backend Implementation
- [ ] FormRequest with validation rules
- [ ] Action class with business logic (`declare(strict_types=1)`)
- [ ] Thin controller using dependency injection
- [ ] Model updates if needed (`$guarded = []`)

### Frontend Implementation
- [ ] TypeScript interface for data model
- [ ] Vue component with `<script setup>` and Composition API
- [ ] Inertia form handling with `useForm()`
- [ ] UI components using Shadcn/Vue with Tailwind

## Implementation Notes
- Related models/components:
- Security considerations:
- Performance considerations:

## Definition of Done
- [ ] All tests passing (`sail composer test`)
- [ ] 90%+ test coverage
- [ ] Code passes linting (`sail composer lint`)
- [ ] Follows PHP 8.4 features (typed props, match, readonly, etc.)
- [ ] Documentation updated (if needed)

## References
- Related issues: 
- Designs/mockups:

## Estimate
- [ ] Small (1-2 hours)
- [ ] Medium (4-8 hours)
- [ ] Large (1-2 days)
