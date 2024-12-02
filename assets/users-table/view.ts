/**
 * WordPress dependencies
 */
import { store, getContext, getElement, withScope } from "@wordpress/interactivity";

/**
 * Internal dependencies
 */
import { useFetch } from './utils';
import { User, UsersContext, Modal } from './types';

/**
 * Internal Constants
 */
const USERS_ENDPOINT = 'cosmo/v1/users';
const USER_HASH_PREFIX = '#user-view-';
const ANIMATION_SPEED = 250;

const getUserContext = getContext<UsersContext>;

const storeDef = {
  state: {
    modalUserName: '',
    openRequests: new Map<string, () => void>(),
    get modal(): Modal {
      const {modalUser, isModalOpen, modalSelectorId} = getUserContext();

      return {
        element: document.getElementById(modalSelectorId),
        isOpen: isModalOpen,
        user: modalUser,
      };
    },
    get baseApiUrl(): string {
      let url = '/wp-json';

      if (!getUserContext().permalinksEnabled) {
        url = '/?rest_route=';
      }

      return url;
    },
    /**
     * Provides the error message if any request is failed.
     */
    get hasError(): boolean {
      return !!getUserContext().error;
    },
    /**
     * Provides the error message if any request is failed to fetch a single user.
     */
    get hasSingleUserError(): boolean {
      return !!getUserContext().singleUserError && state.modal.isOpen;
    },
    /**
     * We hide Table when:
     * - Users are not an array.
     * - Users are an empty array.
     */
    get shouldHideTable(): boolean {
      const {users} = getUserContext();

      return [
        !Array.isArray(users),
        users?.length === 0
      ].some(Boolean);
    },
    /**
     * We hide Skeleton when:
     * - We have an error.
     * - Modal is open, and we don't have a single user yet.
     */
    get shouldHideDetailsSkeleton(): boolean {
      if (state.hasError && state.modal.isOpen) {
        return true;
      }

      return state.modal.isOpen && !!state.modal.user;
    },
    /**
     * We hide User Details when:
     * - We have an error.
     * - Modal is open, and we don't have a single user yet.
     */
    get shouldHideUserDetails(): boolean {
      if (state.hasError && state.modal.isOpen) {
        return true
      }

      return state.modal.isOpen && !state.modal.user?.id;
    },
  },
  actions: {
    /**
     * Makes request to the given URL.
     * And handles the error.
     */
    async apiFetch(url: string, controllable: boolean = false): Promise<any> {
      const {request, abort} = useFetch(url);

      if (controllable) {
        state.openRequests.set(url, abort);
      }

      return request.finally(
        () => {
          if (controllable) {
            state.openRequests.delete(url);
          }
        }
      );
    },
    /**
     * Fetches a single user details by a given user id.
     */
    async fetchUser(id: number): Promise<User> {
      const context = getUserContext();

      context.singleUserError = '';

      return actions
        .apiFetch(`${state.baseApiUrl}/${USERS_ENDPOINT}/${id}`, true)
        .catch(
          withScope((error: Error) => context.singleUserError = error.message)
        );
    },
    /**
     * Fetches all users from a server.
     */
    async fetchAllUsers(): Promise<User[]|string> {
      const context = getUserContext();

      context.error = '';

      return actions
        .apiFetch(`${state.baseApiUrl}/${USERS_ENDPOINT}`)
        .then(
          withScope((users: User[]) => context.users = users)
        )
        .catch(
          withScope((error: Error) => context.error = error.message)
        );
    },
    /**
     * Fetches a single user details by a given user id,
     * and opens modal with the user details.
     */
    * fetchUserAndOpenModal(id: number): Generator<Promise<User|string>> {
      const context = getUserContext();

      context?.users?.forEach((user: User) => {
        if (user.id === id) {
          state.modalUserName = user.name;
        }
      });

      // When we don't have a user yet, we show a loading state.
      if (!state.modalUserName) {
        state.modalUserName = '...';
      }

      // Reset the modal user.
      context.modalUser = null;

      // Open a modal to show a loading state.
      actions.modalOpen();

      // Fetch the user details and open the modal.
      yield actions.fetchUser(id).then(
        withScope((user: User): User => {
          actions.navigate(`${USER_HASH_PREFIX}${id}`);
          state.modalUserName = user.name;
          context.modalUser = user;
          return user;
        })
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
            context.modalUser = null;
          });
        }),
        ANIMATION_SPEED
      )
    },
    /**
     * Handles the close modal button click.
     * Handles the close the escape key press.
     */
    handleClose(evt: Event): void {
      evt.preventDefault();
      actions.modalClose();

      // On slow connections there is a possibility that the user clicks on the close button,
      // and request a new user, so we just abort all previous requests on close modal.
      state.openRequests.forEach((abort) => abort());
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
  },
  callbacks: {
    /**
     * When application starts up, it fetches all users from the server.
     * If the URL hash contains a user id, it fetches the user details and opens the modal.
     */
    * init() {
      yield actions.handleHashChange();
      // Fetch all users.
      yield actions.fetchAllUsers();
    },
  },
};

type Store = typeof storeDef;

/**
 * Note: Even though we don't use destructed values w/i this file, we use them inside the render.php file.
 */
const { state, actions, callbacks } = store< Store >( 'cosmo-users/users-table', storeDef );
