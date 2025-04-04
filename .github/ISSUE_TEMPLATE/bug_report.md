---
name: Bug Report
about: Report a bug with TDD-first fix approach
title: '[BUG] '
labels: 'bug'
assignees: ''

---

## Bug Description
A clear and concise description of what the bug is.

## Steps to Reproduce
1. Go to '...'
2. Click on '....'
3. Scroll down to '....'
4. See error

## Expected Behavior
A clear and concise description of what you expected to happen.

## Current Behavior
What actually happens instead of the expected behavior.

## Technical Analysis

### Test Coverage (TDD-First)
- [ ] Failing test demonstrating the bug
- [ ] Test for the fix implementation
- [ ] Regression tests if needed

### Backend Fix
- [ ] Identify affected Action class
- [ ] Update FormRequest validation if needed
- [ ] Fix core logic issue with strict typing

### Frontend Fix
- [ ] Identify affected Vue component
- [ ] Fix TypeScript interface if needed
- [ ] Address UI/UX issues

## Implementation Notes
- Root cause analysis:
- Affected components:
- Data integrity concerns:

## Definition of Done
- [ ] All tests passing (`sail composer test`)
- [ ] Bug cannot be reproduced
- [ ] Code passes linting (`sail composer lint`)
- [ ] Documentation updated (if affected)

## Additional Context
Add any other context about the problem here (screenshots, logs, etc.)

## References
- Related issues:
- Related commits:

## Priority
- [ ] Low (minor inconvenience)
- [ ] Medium (affects functionality but has workaround)
- [ ] High (blocking feature usage)
- [ ] Critical (system crash or data loss)
