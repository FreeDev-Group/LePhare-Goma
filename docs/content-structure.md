# LE PHARE GOMA: WordPress content structure (issue #13)

Status: **proposed**, to be confirmed with Edouard and DieuMerci before creating the categories.

Principle: reuse standard WordPress features (Pages, Posts, Categories, Featured images, Media Library). No custom post type and no extra plugin is required for this structure.

## 1. Pages

| Page | Slug | Owner issue |
|---|---|---|
| Home | `/` | #01 |
| About Us | `about-us` | #02 |
| Ministries | `ministries` | #03 |
| Small Groups / PFP | `small-groups` | #04 |
| Events | `events` | #05 |
| Sermons & Resources | `sermons` | #06 |
| News & Articles | `news` | #07 |
| Media Gallery | `gallery` | #08 |
| Contact & Location | `contact` | #09 |
| Giving / Financial Partnership | `giving` | #10 |
| Privacy Policy | `privacy-policy` | #11 |

## 2. Posts and categories

Each content item (a sermon, an event, a ministry, a news article) is a **Post** with one category, a title, an excerpt and a featured image. Listing pages query posts by category.

| Category | Slug | Content | Listing page |
|---|---|---|---|
| News & Articles | `news` | News and articles | News & Articles (#07) |
| Sermons | `sermons` | Sermons with speaker, date, Bible reference, media links | Sermons & Resources (#06) |
| Events | `events` | Upcoming and past events | Events (#05) |
| Ministries | `ministries` | One post per ministry | Ministries (#03) |
| Small Groups | `small-groups` | One post per group | Small Groups / PFP (#04) |

Notes:
- The default category "Uncategorized" is renamed to "News & Articles" (slug `news`) and kept as the default category.
- Gallery images are handled on the gallery page by DieuMerci (#08) and optimized in #16.

## 3. Roles and editing access

| Who | WordPress role | Can do |
|---|---|---|
| Church administrators | Administrator | Manage everything |
| Church content editors (optional) | Editor | Create, edit and publish all pages and posts, without access to themes, plugins or settings |
| Development team | Administrator (development site only) | Build and configure |

Note: the real accounts for church administrators and editors are created on the live site, by the person who has administrator access to it (at deployment, issue #20). On each local copy, only test accounts are used to check what each role can do.

## 4. To agree with the team

- Events: how upcoming vs past events are separated (post date, or a custom field).
- Sermons: where the speaker, date, Bible reference and media links are written (excerpt, post content or custom fields).
- Ministries: which fields every ministry post must contain (description, activities, meeting information, responsible person).

## 5. Checklist (acceptance criteria of #13)

- [ ] Categories created with the slugs above
- [ ] Default category renamed to "News & Articles"
- [ ] Administrator role can create, edit and publish pages and posts (tested on a local copy)
- [ ] Real administrator and editor accounts to be created on the live site at deployment (#20)
- [ ] A test post can be created, edited and published in each category
- [ ] No custom code or extra plugin added for content types

## 6. Deployment note

Categories and user accounts are stored in the WordPress database, not in Git. The five categories (same names and slugs as in section 2) must be created again on the live site, and the real accounts created there as described in section 3.
