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
  permalinksEnabled: boolean;
  modalSelectorId: string;
  singleUserError: string;
  currentOffset: number;
  isModalOpen: boolean;
  users: Array<User>;
  modalUser: User|null;
  error: string;
};

export type Modal = {
  element: HTMLElement | null;
  isOpen: boolean;
  user: User;
}
