export type Company = {
  name: string;
  slogan: string;
}

export type User = {
  id: number;
  name: string;
  email: string;
  username: string;
  website: string;
  phone: string;
  company: Company;
}

export type UsersContext = {
  users: Array<User>;
  currentUser: User;
  isModalOpen: boolean;
  currentOffset: number;
  modalSelectorId: string;
  isPermalinkEnabled: boolean;
};

export type Modal = {
  user: User;
  isOpen: boolean;
  element: HTMLElement | null;
}
