<?php

/**
 * PHP file to use when rendering the block type on the server to show on the front end.
 *
 * The following variables are exposed to the file:
 *     $attributes (array): The block attributes.
 *     $content (string): The block default content.
 *     $block (WP_Block): The block instance.
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */

declare(strict_types=1);

// Generates a unique id for aria-controls.
$uniqueId = wp_unique_id('cosmoUser-');

/**
 * We need to specify the context from the server side, to avoid state discrepancies.
 */
$context = [
    'permalinksEnabled' => (bool) get_option('permalink_structure'),
    'modalSelectorId' => sprintf('%s-dialog', $uniqueId),
    'isModalOpen' => false,
    'modalUser' => null,
    'error' => null,
    'users' => [],
];

/**
 * Show in modal the username if it's known, while loading the rest of the user data.
 */
wp_interactivity_state('cosmo-users/users-table', [
    'shouldHideSkeleton' => true,
    'hasSingleUserError' => false,
    'shouldHideTable' => true,
    'modalUserName' => null,
    'openRequests' => [],
    'hasError' => false,
]);
?>

<div
    id="single-user-preview"
    <?php echo wp_kses_data(get_block_wrapper_attributes()); ?>
    <?php echo wp_kses_data(wp_interactivity_data_wp_context($context)); ?>
    data-wp-on-async-window--hashchange="actions.handleHashChange"
    data-wp-interactive="cosmo-users/users-table"
    data-wp-init="callbacks.init"
>
    <?php
    // Show loader or error message when users failed to load.
    ?>
    <div data-wp-class--hidden="!state.shouldHideTable">
        <span
            data-wp-class--hidden="state.hasError"
        ><?php esc_html_e('Loading...', 'cosmo-users'); ?></span>
        <span
            data-wp-class--hidden="!state.hasError"
            data-wp-text="context.error"
            class="error"
        ></span>
    </div>

    <?php
    // The dialog that will show the user details.
    ?>
    <dialog
        role="alertdialog"
        class="cosmo-users--overlay"
        id="<?php echo esc_attr($uniqueId); ?>-dialog"
        data-wp-class--is-active="state.modal.isOpen"
        data-wp-on--cancel="actions.handleClose"
    >
        <section class="cosmo-users--overlay__header">
            <h2>
                <span>
                    <?php esc_html_e('User Details:', 'cosmo-users'); ?>
                    <span data-wp-text="state.modalUserName"></span>
                </span>
            </h2>
            <button
                aria-label="<?php esc_attr_e('Close dialog', 'cosmo-users'); ?>"
                data-wp-on--click="actions.handleClose"
                class="cosmo-users--overlay__close"
            ></button>
        </section>

        <section class="cosmo-users--overlay__content">

            <div
                data-wp-class--hidden="state.shouldHideDetailsSkeleton"
                class="skeleton hidden"
            >
                <?php
                echo wp_kses(
                    str_repeat('<div class="placeholder text full"></div>', 8),
                    ['div' => ['class' => []]]
                );
                ?>
            </div>

            <dl
                data-wp-class--hidden="state.shouldHideUserDetails"
                class="cosmo-users--overlay__content-details"
            >
                <dt><?php esc_html_e('ID', 'cosmo-users'); ?></dt>
                <dd data-wp-text="context.modalUser.id"></dd>
                <dt><?php esc_html_e('Name', 'cosmo-users'); ?></dt>
                <dd data-wp-text="context.modalUser.name"></dd>
                <dt><?php esc_html_e('Username', 'cosmo-users'); ?></dt>
                <dd data-wp-text="context.modalUser.username"></dd>
                <dt><?php esc_html_e('Email', 'cosmo-users'); ?></dt>
                <dd data-wp-text="context.modalUser.email"></dd>
                <dt><?php esc_html_e('Phone', 'cosmo-users'); ?></dt>
                <dd data-wp-text="context.modalUser.phone"></dd>
                <dt><?php esc_html_e('Website', 'cosmo-users'); ?></dt>
                <dd data-wp-text="context.modalUser.website"></dd>
                <dt><?php esc_html_e('Company', 'cosmo-users'); ?></dt>
                <dd data-wp-text="context.modalUser.company.name"></dd>
            </dl>

            <div
                data-wp-class--hidden="!state.hasSingleUserError"
                class="hidden"
            >
                <span data-wp-text="context.singleUserError" class="error"></span>
            </div>
        </section>
    </dialog>

    <table
        role="table"
        data-wp-class--hidden="state.shouldHideTable"
        class="cosmo-users--users-table hidden"
        aria-labelledby="user-table-caption"
    >
        <caption
            role="caption"
            id="user-table-caption"
        >
            <?php esc_html_e('List of Cosmo Users', 'cosmo-users') ?>
        </caption>
        <thead role="rowgroup">
            <tr>
                <th scope="col"><?php esc_html_e('ID', 'cosmo-users') ?></th>
                <th scope="col"><?php esc_html_e('Full Name', 'cosmo-users') ?></th>
                <th scope="col"><?php esc_html_e('User Name', 'cosmo-users') ?></th>
                <th scope="col"><?php esc_html_e('Email', 'cosmo-users') ?></th>
                <th scope="col"><?php esc_html_e('Phone', 'cosmo-users') ?></th>
            </tr>
        </thead>
        <tbody role="rowgroup">
            <template
                data-wp-each--user="context.users"
                data-wp-each-key="user.id"
            >
                <tr
                    role="row"
                    aria-label="<?php esc_attr_e('View user details', 'cosmo-users'); ?>"
                    data-wp-on--click="actions.handleClick"
                    data-wp-bind--data-id="context.user.id"
                >
                    <td
                        role="cell"
                        aria-label="<?php esc_attr_e('ID', 'cosmo-users'); ?>"
                    >
                        <a data-wp-text="context.user.id" href="#single-user-preview"></a>
                    </td>
                    <td
                        role="cell"
                        aria-label="<?php esc_attr_e('Full Name', 'cosmo-users'); ?>"
                    >
                        <a data-wp-text="context.user.name" href="#single-user-preview"></a>
                    </td>
                    <td
                        role="cell"
                        aria-label="<?php esc_attr_e('UserName', 'cosmo-users'); ?>"
                    >
                        <a data-wp-text="context.user.username" href="#single-user-preview"></a>
                    </td>
                    <td
                        role="cell"
                        aria-label="<?php esc_attr_e('Email', 'cosmo-users'); ?>"
                    >
                        <a data-wp-text="context.user.email" href="#single-user-preview"></a>
                    </td>
                    <td
                        role="cell"
                        aria-label="<?php esc_attr_e('Phone', 'cosmo-users'); ?>"
                    >
                        <a data-wp-text="context.user.phone" href="#single-user-preview"></a>
                    </td>
                </tr>
            </template>
        </tbody>
    </table>
</div>
