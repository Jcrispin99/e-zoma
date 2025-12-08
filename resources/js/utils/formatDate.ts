export const formatDate = (
  date: string | Date,
  options: Intl.DateTimeFormatOptions = {}
): string => {
  if (!date) return '-';

  const d = new Date(date);
  if (isNaN(d.getTime())) return '-';

  const defaultOptions: Intl.DateTimeFormatOptions = {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    ...options,
  };

  return d.toLocaleString('es-PE', defaultOptions);
};
