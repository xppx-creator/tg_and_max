import { makeCacheChannel } from '@makeroi/cache';
import { LocalStorageCacheDriver } from '@makeroi/cache/Drivers/LocalStorage';

let store: LocalStorageCacheDriver | undefined;

export function getLocalStore(): LocalStorageCacheDriver {
  return store ?? (store = makeCacheChannel('makeroi-analitycs-table-v8', { driver: 'local' }));
}
