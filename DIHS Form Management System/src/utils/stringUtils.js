function capitalize(str) {
  if (!str) return '';
  return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

function truncate(str, maxLength, ellipsis = '...') {
  if (!str || str.length <= maxLength) return str;
  return str.substring(0, maxLength) + ellipsis;
}

function slugify(str) {
  if (!str) return '';
  return str
    .toLowerCase()
    .trim()
    .replace(/[^\w\s-]/g, '')
    .replace(/[\s_-]+/g, '-')
    .replace(/^-+|-+$/g, '');
}

module.exports = {
  capitalize,
  truncate,
  slugify
};
