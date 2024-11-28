/**
 * WordPress dependencies
 */
import { store, getContext, getElement, withScope } from "@wordpress/interactivity";

/**
 * Internal dependencies
 */
import { User, UsersContext, Modal } from './types';

/**
 * Internal Constants
 */
const USERS_ENDPOINT = '/cosmo/v1/users';
const USER_HASH_PREFIX = '#user-view-';
const ANIMATION_SPEED = 250;

type ApiOptions = {
  path: string;
}

/**
 * While apiFetch is not possible to use in the modules mode, meanwhile can use the polyfill.
 * - No Nonce handler, just fetch data from the API
 * - No Prefetching, just fetch data when needed
 *
 * @param options<ApiOptions> Options to pass to the fetch function.
 *
 * @return {Promise<User|User[]>} The fetch promise.
 */
const apiFetch = async (options: ApiOptions): Promise<User | User[]> => {
  let baseUrl = `/wp-json${options.path}`;

  if (! state.isPermalinkEnabled) {
    baseUrl = `/?rest_route=${options.path}`;
  }

  return fetch(baseUrl, {
    headers: {
      Accept: 'application/json, */*;q=0.1',
    }
  }).then(response => response.json());
};

const getUserContext = getContext<UsersContext>;

const storeDef = {
  state: {
    requestedUserName: '',
    get modal(): Modal {
      const {currentUser, isModalOpen, modalSelectorId} = getUserContext();

      return {
        user: currentUser,
        isOpen: isModalOpen,
        element: document.getElementById(modalSelectorId),
      };
    },
    get isSingleUserLoaded(): boolean {
      return !!getUserContext().currentUser;
    },
    get isPermalinkEnabled(): boolean {
      return !!getUserContext().isPermalinkEnabled;
    }
  },
  actions: {
    /**
     * When application starts up, it fetches all users from the server.
     * If the URL hash contains a user id, it fetches the user details and opens the modal.
     */
    * startUp() {
      yield callbacks.handleHashChange();
      // Fetch all users.
      yield actions.fetchAllUsers();
    },
    /**
     * Fetches a single user details by a given user id.
     */
    fetchUser(id: number): Promise<User> {
      const context = getUserContext();

      return apiFetch({path: `${USERS_ENDPOINT}/${id}`}).then(
        withScope((user: User) => context.currentUser = user)
      );
    },
    /**
     * Fetches a single user details by a given user id,
     * and opens modal with the user details.
     */
    * fetchUserAndOpenModal(id: number): Generator<Promise<User>> {
      const {users = []} = getUserContext();

      users.forEach((user: User) => {
        if (user.id === id) {
          state.requestedUserName = user.name;
        }
      });

      // When we don't have a user yet, we show a loading state.
      if (!state.requestedUserName) {
        state.requestedUserName = '...';
      }

      // Open a modal to show a loading state.
      actions.modalOpen();

      // Fetch the user details and open the modal.
      yield actions.fetchUser(id).then(
        withScope((user: User) => {
          actions.navigate(`${USER_HASH_PREFIX}${id}`);
          state.requestedUserName = user.name;
          return user;
        })
      );
    },
    /**
     * Fetches all users from a server.
     */
    fetchAllUsers(): Promise<User[]> {
      const context = getUserContext();

      return apiFetch({path: USERS_ENDPOINT}).then(
        withScope((users: User[]) => context.users = users)
      );
    },
    /**
     * Navigates to the given href.
     */
    * navigate(href: string) {
      const {actions} = yield import('@wordpress/interactivity-router');
      yield actions.navigate(href);
    },
    /**
     * Opens the modal.
     */
    modalOpen(): void {
      const context = getUserContext();

      context.isModalOpen = true;

      (state.modal.element as HTMLDialogElement)?.showModal();
    },
    /**
     * Closes the modal and resets the current user as they are no longer needed.
     */
    modalClose(): void {
      const context = getUserContext();

      context.isModalOpen = false;

      setTimeout(
        withScope(() => {
          (state.modal.element as HTMLDialogElement)?.close();

          const {location} = window || {location: {origin: '', pathname: '', search: ''}};

          // Go back to the original URL, w/o the user hash.
          const originUrl = `${location.origin}${location.pathname}${location.search}`;

          actions.navigate(originUrl).then(() => {
            context.currentUser = null;
          });
        }),
        ANIMATION_SPEED
      )
    },
  },
  callbacks: {
    /**
     * Opens/Closes the modal when the URL hash changes.
     */
    async handleHashChange() {
      const {hash} = window.location;

      if (hash?.startsWith(USER_HASH_PREFIX)) {
        const userId = parseInt(hash.replace(USER_HASH_PREFIX, ''));

        if (Number.isInteger(userId)) {
          await actions.fetchUserAndOpenModal(userId);
        }
      }

      // Close the modal when the hash is empty.
      // E.g. when the user navigates back to the original URL.
      if (hash === '' && state.modal.isOpen) {
        actions.modalClose();
      }
    },
    /**
     * Handles the click on the user row.
     */
    async handleClick(evt: Event) {
      if (evt.target instanceof HTMLAnchorElement) {
        evt.preventDefault();

        const {
          ref: {dataset: {id}},
        } = getElement();

        await actions.fetchUserAndOpenModal(parseInt(id));
      }
    },
    /**
     * Handles the close modal button click.
     * Handles the close the escape key press.
     */
    handleClose(evt: Event): void {
      evt.preventDefault();

      actions.modalClose();
    },
  },
};

type Store = typeof storeDef;

/**
 * Note: Even though we don't use destructed values w/i this file, we use them inside the render.php file.
 */
const { state, actions, callbacks } = store< Store >( 'cosmo-users/users-table', storeDef );
