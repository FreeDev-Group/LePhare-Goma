# LePhare Goma

### WordPress Website Development & Maintenance


\


**Live Website:** [https://lepharegoma.org](https://lepharegoma.org)
**Source Repository:** [https://github.com/FreeDev-Group/LePhare-Goma](https://github.com/FreeDev-Group/LePhare-Goma)

---

## 1. Project Overview

LePhare Goma is a WordPress website project maintained by a distributed development team. The project focuses on website maintenance, technical improvements, troubleshooting, testing, and the controlled delivery of changes to the production environment.

The team collaborates remotely, with each developer responsible for assigned tasks or specific areas of the project. GitHub provides the shared repository and issue-tracking workflow used to document progress, coordinate contributions, and support technical review.

### Project Information

| Attribute         | Details                                            |
| ----------------- | -------------------------------------------------- |
| Project           | LePhare Goma                                       |
| Website           | [https://lepharegoma.org](https://lepharegoma.org) |
| CMS               | WordPress                                          |
| Hosting Provider  | Hostinger                                          |
| Domain            | lepharegoma.org                                    |
| Development Model | Remote team collaboration                          |
| Version Control   | Git and GitHub                                     |
| Task Management   | GitHub Issues                                      |
| Project Mentor    | Gauthier                                            |

## 2. Team & Responsibilities

The project is maintained by four developers working under the guidance of a project mentor.

| Team Member | Responsibility |
| ----------- | -------------- |
| MUGISHO     | Developer      |
| ARON        | Developer      |
| ARNOLD      | Developer      |
| EDWARD      | Developer      |
| GOTHIER     | Project Mentor |

### Collaboration Principles

- Each developer works on assigned tasks or designated areas of the website.
- Responsibilities and task progress are documented through GitHub Issues.
- Developers coordinate changes that may affect shared files or functionality.
- Significant work is documented so that other team members can understand and review it.
- The project mentor provides feedback and reviews work according to the agreed workflow.

Detailed individual assignments should be maintained in GitHub Issues rather than hard-coded into this README.

## 3. Technology & Infrastructure

### Confirmed Technologies and Services

- **WordPress:** Content management system.
- **Git:** Source-code version control.
- **GitHub:** Shared repository and issue tracking.
- **Hostinger:** Hosting provider for the production website.
- **Local development environments:** Each team member has a local WordPress installation.

The exact WordPress version, PHP version, database engine, installed plugins, active theme, and server configuration must be verified before being documented as project requirements.

## 4. Repository Organization

The repository currently contains the following top-level directories:

```text
LePhare-Goma/
├── app/
│   └── public/
├── conf/
└── logs/
```

**Directory responsibilities**

- `app/` — Application-related files, including the website's public files.
- `app/public/` — Public-facing website files, including WordPress files.
- `conf/` — Server and service configuration files.
- `logs/` — Log-related directory.

This is a high-level overview. The complete directory structure and the purpose of individual configuration files should be verified against the current repository before further documentation is added.

## 5. Local Development

Each team member maintains a local WordPress installation for implementing and testing assigned changes.

### Development Guidelines

1. Review the relevant GitHub Issue before starting work.
2. Confirm the scope of the task and identify affected files or components.
3. Ensure the local environment is configured correctly.
4. Implement only the changes required for the assigned task.
5. Test the affected pages, functionality, and relevant edge cases.
6. Review the changes before committing them.
7. Document relevant findings, test results, and outstanding issues.

Local WordPress installations may have separate databases, configuration values, plugins, themes, and uploaded media. These components must not be assumed to synchronize automatically through Git.

## 6. Git Workflow & Code Management

All source-code contributions should be managed through Git and the shared GitHub repository.

### Contribution Guidelines

- Keep changes focused on the assigned task.
- Avoid committing unrelated files or generated artifacts.
- Review the working tree before staging changes.
- Write clear, descriptive commit messages.
- Coordinate changes to shared files with other developers.
- Avoid overwriting or reverting another developer's work without coordination.
- Review the final diff before pushing changes.

Where the team's repository permissions and workflow support it, use feature branches and pull requests to facilitate review before merging changes into the main branch.

The exact branching and merge strategy should be agreed upon by the team and mentor.

## 7. Issue Tracking & Technical Review

GitHub Issues are the central record for significant development and maintenance tasks.

Each issue should document:

- **Title:** A concise description of the task.
- **Objective:** The problem to solve or outcome to achieve.
- **Scope:** The expected changes and relevant components.
- **Assignee:** The team member responsible for delivery.
- **Acceptance criteria:** The conditions required for completion.
- **Implementation notes:** Relevant technical decisions and findings.
- **Testing:** The checks performed and their results.
- **Review:** Mentor feedback, comments, and required corrections.
- **Resolution:** A summary of the completed work.

### Task Lifecycle

**Issue creation → Assignment → Implementation → Testing → Review → Approved deployment, when applicable → Verification → Closure**

Issues should remain open when required review, corrections, or verification are still outstanding.

## 8. Testing & Quality Assurance

Changes must be tested in the appropriate development environment before they are considered ready for production.

Depending on the task, verification may include:

- Page rendering and responsive layout.
- Navigation, forms, and interactive components.
- WordPress administration functionality.
- Plugin and theme compatibility.
- PHP errors and relevant application logs.
- Database-dependent functionality.
- Regression checks for affected pages and features.

Testing results and known limitations should be recorded in the corresponding GitHub Issue.

The project's formal testing checklist should be expanded as the team establishes its quality-assurance procedures.

## 9. Production Hosting & Deployment

The production website is hosted on Hostinger, and the domain `lepharegoma.org` is configured for the project.

**Production URL:** [https://lepharegoma.org](https://lepharegoma.org)

Production changes must be coordinated to reduce downtime, data loss, and conflicts between team members.

### Production Change Requirements

1. Confirm that the task has been implemented and tested.
2. Review the proposed changes and obtain the required approval.
3. Back up the relevant website files and database before significant changes.
4. Deploy using the team's approved procedure.
5. Verify the affected functionality on the live website.
6. Record the deployment outcome in the relevant GitHub Issue.

The actual deployment method, rollback procedure, backup schedule, and responsibility for production releases must be documented once confirmed by the team.

## 10. Security & Sensitive Information

Security is a shared responsibility throughout development, review, and deployment.

- Never commit passwords, API keys, authentication tokens, private keys, or database credentials.
- Do not store production secrets in the repository or README.
- Review configuration files and staged changes before every commit.
- Exclude local environment files, backups, database dumps, and sensitive logs when they are not explicitly required and approved for version control.
- Apply WordPress, plugin, theme, and server updates through a controlled and tested process.
- Restrict production access to authorized team members.
- Maintain recoverable backups before significant production changes.
- Report suspected credential exposure or security vulnerabilities through a private, appropriate channel.

A `.gitignore` file and repository security review should be maintained as part of the team's development standards.

## 11. Project Documentation

This README serves as the project's entry point. Detailed operational information should be documented separately when available, including:

- Local development setup.
- Verified software and dependency requirements.
- Repository branching and review conventions.
- Testing procedures.
- Backup and recovery instructions.
- Production deployment and rollback procedures.
- Team assignments and maintenance history.

Documentation must reflect verified project practices and remain synchronized with changes to the codebase and infrastructure.

## 12. Project Status

The following project details have been established:

- WordPress is installed in the local development environment of each team member.
- The project uses a shared GitHub repository.
- The domain and hosting are configured through Hostinger.
- WordPress is installed in the hosted environment.
- The remote team is organized around assigned tasks and mentor review.

Further operational details should be recorded as the team verifies and formalizes its workflow.

---

**Maintained by:** Gothier Team
**Project Mentor:** Gothier
**Repository:** [https://github.com/FreeDev-Group/LePhare-Goma](https://github.com/FreeDev-Group/LePhare-Goma)
**Website:** [https://lepharegoma.org](https://lepharegoma.org)


