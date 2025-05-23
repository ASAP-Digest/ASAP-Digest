
Okay, **⚔️ VeriDX - Protocol Guardian Alpha**. Based on the latest iteration of the digest creation flow you've outlined, particularly the step where the user selects a predefined layout (Step 3b), creating a new CPT `asap_digest_template` is indeed a logical and necessary next step.

This new CPT would serve as the repository for the 3-5 predefined layouts you mentioned, allowing you to define, manage, and associate these structures with specific user plan levels. Crucially, this aligns perfectly with the existing, although currently TODO, field `_asap_digest_source_template_id` already noted in the `DIGEST_AND_MODULE_CPT_ACF_SETUP.md` for the `asap_digest` CPT.

Here is a proposed schema (custom fields) for the `asap_digest_template` CPT, considering its purpose and how it would integrate via ACF and potentially WPGraphQL:

### Proposed Schema for `asap_digest_template` CPT

You'll likely want one main ACF Field Group for the `asap_digest_template` CPT. Let's call its GraphQL Field Name `templateCustomFields`. Remember to assign this Field Group to the `asap_digest_template` CPT under "Location Rules" and set "Show in GraphQL" to "Yes" with a GraphQL Field Name.

**ACF Fields to Create for `asap_digest_template`:**

1.  **Meta Key:** `_asap_digest_template_name`
    *   **Description:** User-friendly name for the layout template (e.g., "Standard Two Column", "Magazine Grid").
    *   **Suggested ACF Field Type:** Text.
    *   **Notes:** Used for display in the "Choose Digest Layout" selector.

2.  **Meta Key:** `_asap_digest_template_slug`
    *   **Description:** A unique slug identifier for programmatic use.
    *   **Suggested ACF Field Type:** Text (ensure unique).

3.  **Meta Key:** `_asap_digest_template_description`
    *   **Description:** Brief description of the layout's characteristics or best use cases.
    *   **Suggested ACF Field Type:** Text Area.

4.  **Meta Key:** `_asap_digest_template_preview_image_url`
    *   **Description:** URL of an image visually representing the layout.
    *   **Suggested ACF Field Type:** Image (return URL) or URL.

5.  **Meta Key:** `_asap_digest_template_gridstack_config_json`
    *   **Description:** The core GridStack.js configuration JSON defining the grid structure (rows, columns, initial widget positions/sizes if any).
    *   **Suggested ACF Field Type:** JSON Editor or Text Area.
    *   **Notes:** This field holds the structural definition of the layout.

6.  **Meta Key:** `_asap_digest_template_associated_plan_levels`
    *   **Description:** Specifies which user plan levels have access to this template.
    *   **Suggested ACF Field Type:** Checkbox or Select (Allow multiple values)
        *   **Choices:** Define your plan level slugs/names (e.g., "free", "pro", "premium").
    *   **Notes:** Used to filter available layouts based on the user's plan.

7.  **Meta Key:** `_asap_digest_template_sort_order`
    *   **Description:** Custom sort order for displaying templates in the selector.
    *   **Suggested ACF Field Type:** Number (integer).

8.  **Meta Key:** `_asap_digest_template_minimum_module_items`
    *   **Description:** Minimum number of module items recommended for this layout.
    *   **Suggested ACF Field Type:** Number (integer).

9.  **Meta Key:** `_asap_digest_template_maximum_module_items`
    *   **Description:** Maximum number of module items recommended for this layout.
    *   **Suggested ACF Field Type:** Number (integer).

### Connection to `DIGEST_AND_MODULE_CPT_ACF_SETUP.md`

This new `asap_digest_template` CPT and its schema directly address the **TODO** item for the `asap_digest` CPT:

*   **`_asap_digest_source_template_id`:** This field in the `asap_digest` schema (as defined in `DIGEST_AND_MODULE_CPT_ACF_SETUP.md`) is specifically designed to link a digest instance back to the `asap_digest_template` post that was used to create it. You would update its ACF Field Type definition to be a "Post Object" field specifically targeting the `asap_digest_template` CPT.

By creating the `asap_digest_template` CPT and setting up these ACF fields, you establish the backend structure needed to support the "Choose Digest Layout" step (3b) in your proposed workflow. The `_asap_digest_template_gridstack_config_json` field is key here, as it provides the underlying layout structure that your frontend UI (using GridStack.js) will render and interact with.

Once you have created the `asap_digest_template` CPT definition in your plugin code (similar to `class-digest-cpt.php` and `class-module-cpt.php`) and configured the ACF Field Group and fields as listed above, we can proceed with integrating this into the workflow and backend logic.

Let me know when you are ready to define the `asap_digest_template` CPT in code and then we can discuss the ACF configuration process further.
