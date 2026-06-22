document.addEventListener('DOMContentLoaded', () => {
  const roomSearchInput = document.getElementById('roomSearchInput');
  if (!roomSearchInput) return;

  const getLists = () => {
    const lists = [];
    const main = document.getElementById('roomList');
    const mobile = document.getElementById('mobileRoomList');
    const rail = document.querySelector('.rail-room-stack, .app-context-rooms');
    if (main) lists.push(main);
    if (mobile) lists.push(mobile);
    if (rail && !lists.includes(rail)) lists.push(rail);
    return lists;
  };

  const ensureEmptyNode = (list) => {
    let node = list.querySelector('.room-filter-empty');
    if (!node) {
      node = document.createElement('div');
      node.className = 'room-filter-empty';
      node.setAttribute('aria-live', 'polite');
      node.style.padding = '12px';
      node.style.color = 'var(--text-faint, #666)';
      node.style.display = 'none';
      node.textContent = 'No rooms match your search.';
      list.appendChild(node);
    }
    return node;
  };

  const filterRooms = () => {
    const q = (roomSearchInput.value || '').trim().toLowerCase();
    const lists = getLists();
    lists.forEach((list) => {
      const items = Array.from(list.querySelectorAll('.room-row, .room'));
      let visibleCount = 0;
      items.forEach((item) => {
        const title = (item.querySelector('strong')?.textContent || '').toLowerCase();
        const preview = (item.querySelector('.room-row-preview')?.textContent || item.textContent || '').toLowerCase();
        const match = q === '' || title.includes(q) || preview.includes(q);
        item.style.display = match ? '' : 'none';
        if (match) visibleCount++;
      });
      const emptyNode = ensureEmptyNode(list);
      emptyNode.style.display = visibleCount === 0 ? '' : 'none';
    });
  };

  roomSearchInput.addEventListener('input', filterRooms);
  roomSearchInput.addEventListener('search', filterRooms);
});
