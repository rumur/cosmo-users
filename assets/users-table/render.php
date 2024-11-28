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
 * We need to specify the context from the server site, to avoid state discrepancies.
 */
$context = [
    'users' => [],
    'currentUser' => null,
    'isModalOpen' => false,
    'isPermalinkEnabled' => (bool) get_option('permalink_structure'),
    'modalSelectorId' => sprintf('%s-dialog', $uniqueId),
];

/**
 * Show in modal the username if it's known, while loading the rest of the user data.
 */
wp_interactivity_state('cosmo-users/users-table', [
    'requestedUserName' => null,
]);
?>

<div
    id="single-user-preview"
    <?php echo wp_kses_data(get_block_wrapper_attributes()); ?>
    <?php echo wp_kses_data(wp_interactivity_data_wp_context($context)); ?>
    data-wp-on-async-window--hashchange="callbacks.handleHashChange"
    data-wp-interactive="cosmo-users/users-table"
    data-wp-init="actions.startUp"
>
    <div data-wp-class--hidden="context.users.length">
        <?php esc_html_e('Loading...', 'cosmo-users'); ?>
    </div>

    <dialog
        role="alertdialog"
        class="cosmo-users--overlay"
        id="<?php echo esc_attr($uniqueId); ?>-dialog"
        data-wp-class--is-active="context.isModalOpen"
        data-wp-on--cancel="callbacks.handleClose"
    >
        <section class="cosmo-users--overlay__header">
            <h2>
                <span>
                    <?php esc_html_e('User Details:', 'cosmo-users'); ?>
                    <span data-wp-text="state.requestedUserName"></span>
                </span>
            </h2>
            <button
                class="cosmo-users--overlay__close"
                data-wp-on--click="callbacks.handleClose"
                aria-label="<?php esc_attr_e('Close dialog', 'cosmo-users'); ?>"
            ></button>
        </section>

        <section class="cosmo-users--overlay__content">

            <div
                class="skeleton"
                data-wp-class--hidden="state.isSingleUserLoaded"
            >
                <?php
                echo wp_kses(
                    str_repeat('<div class="placeholder text full"></div>', 8),
                    ['div' => ['class' => []]]
                );
                ?>
            </div>

            <dl
                class="cosmo-users--overlay__content-details"
                data-wp-class--hidden="!state.isSingleUserLoaded"
            >
                <dt><?php esc_html_e('ID', 'cosmo-users'); ?></dt>
                <dd data-wp-text="context.currentUser.id"></dd>
                <dt><?php esc_html_e('Name', 'cosmo-users'); ?></dt>
                <dd data-wp-text="context.currentUser.name"></dd>
                <dt><?php esc_html_e('Username', 'cosmo-users'); ?></dt>
                <dd data-wp-text="context.currentUser.username"></dd>
                <dt><?php esc_html_e('Email', 'cosmo-users'); ?></dt>
                <dd data-wp-text="context.currentUser.email"></dd>
                <dt><?php esc_html_e('Phone', 'cosmo-users'); ?></dt>
                <dd data-wp-text="context.currentUser.phone"></dd>
                <dt><?php esc_html_e('Website', 'cosmo-users'); ?></dt>
                <dd data-wp-text="context.currentUser.website"></dd>
                <dt><?php esc_html_e('Company', 'cosmo-users'); ?></dt>
                <dd data-wp-text="context.currentUser.company.name"></dd>
            </dl>
        </section>
    </dialog>

    <table
        role="table"
        class="cosmo-users--users-table hidden"
        aria-labelledby="user-table-caption"
        data-wp-class--hidden="!context.users.length"
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
                    data-wp-on--click="callbacks.handleClick"
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
