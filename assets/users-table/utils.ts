export const useFetch = <T> (url: string, options: RequestInit = {}) => {
  const controller = new AbortController();
  const { signal } = controller;

  const request = fetch(url, {...options, signal})
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP Error: ${response.statusText}`);
      }
      return response.json() as Promise<T>;
    })
    .catch((error) => {
      if (error.name === 'AbortError') {
        console.log('Fetch aborted');
      } else {
        console.error('Fetch error:', error.message);
      }
      throw error;
    });

  return {
    abort: () => controller.abort(),
    request,
  };
};
