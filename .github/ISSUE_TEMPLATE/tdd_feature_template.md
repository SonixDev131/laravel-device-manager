---
name: TDD Feature or Task
about: Use this template for creating new features or tasks with a TDD-first approach.
title: '[Feature/Task] <Short description of the feature or task>'
labels: feature, tdd
assignees: <your-github-username>
---

## TDD Feature/Task

### User Story
As a [type of user], I want to [action or feature] so that [benefit or goal].

### Description
Provide a brief overview of the feature or task. What does it do? Why is it needed?

### Acceptance Criteria
List the specific conditions that must be met for this feature/task to be considered complete.
- [ ] Criterion 1 (e.g., User can log in with valid credentials and receives a 200 response).
- [ ] Criterion 2 (e.g., User sees an error message with invalid credentials and receives a 401 response).

### Test Cases (TDD-First)
List the test cases you will write before implementing the feature. These should fail initially (Red phase).
- [ ] Test case 1 (e.g., Test that a user with valid credentials can log in successfully).
- [ ] Test case 2 (e.g., Test that a user with invalid credentials receives an error).

### Implementation Notes
Add any technical details, dependencies, or considerations for implementation.
- Example: "This feature requires setting up a database to store user credentials."
- Example: "Use the Flask framework for the backend."

### Priority
- [ ] High
- [ ] Medium
- [ ] Low

### Estimated Effort
Estimate the time or effort required (e.g., 2 hours, 1 day).

### Additional Context
Add any other relevant information, such as links to documentation, designs, or related issues.

---

**Checklist for TDD Workflow**
- [ ] Write failing tests (Red phase)
- [ ] Implement code to pass tests (Green phase)
- [ ] Refactor code while ensuring tests pass
- [ ] Create a pull request and link to this issue
- [ ] Merge and close the issue
