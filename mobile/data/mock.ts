export const CATEGORIES = [
  { 
    id: 1, 
    name: 'Divertissement', 
    icon: 'film', 
    emoji: '🎬', 
    keywords: [
      'netflix', 'disney', 'hulu', 'tv', 'movie', 'film', 'cinema', 
      'stream', 'subscription', 'plus', 'premium', 'twitch', 'crunchyroll',
      'canal', 'dstv', 'showmax', 'startimes', 'youtube', 'prime'
    ] 
  },
  { 
    id: 2, 
    name: 'Jeux Vidéo', 
    icon: 'gamepad-2', 
    emoji: '🎮', 
    keywords: [
      'playstation', 'xbox', 'nintendo', 'steam', 'roblox', 'minecraft', 
      'fortnite', 'pubg', 'game', 'gaming', 'fire', 'diamond', 'uc', 
      'credits', 'valorant', 'league', 'legend', 'fifa', 'nba', 
      'call of duty', 'cod', 'mobile legends', 'coins', 'points', 'card', 'code',
      'brawl', 'clash', 'royale', 'apex', 'overwatch', 'blizzard', 'ea', 'ubisoft'
    ] 
  },
  { 
    id: 3, 
    name: 'Musique', 
    icon: 'music', 
    emoji: '🎵', 
    keywords: [
      'spotify', 'apple', 'itunes', 'music', 'deezer', 'shazam', 
      'song', 'audio', 'sound', 'tidal', 'napster', 'amazon music', 'youtube music'
    ] 
  },
  { 
    id: 4, 
    name: 'Shopping', 
    icon: 'shopping-cart', 
    emoji: '🛍️', 
    keywords: [
      'amazon', 'ebay', 'walmart', 'bestbuy', 'target', 'shopping', 'store', 
      'google play', 'apple store', 'gift', 'voucher', 'coupon', 
      'nike', 'adidas', 'shein', 'zalando', 'asos', 'fnac', 'darty', 'cdiscount',
      'rakuten', 'alibaba', 'aliexpress', 'jumia', 'konga'
    ] 
  },
  {
    id: 5,
    name: 'Daywatch',
    icon: 'tv',
    emoji: '📺',
    keywords: [
      'daywatch', 'day watch', 'streaming local', 'tv'
    ]
  },
  { 
    id: 6, 
    name: 'Voyage', 
    icon: 'map', 
    emoji: '✈️', 
    keywords: [
      'uber', 'airbnb', 'booking', 'expedia', 'travel', 'flight', 'hotel',
      'train', 'bus', 'trip', 'bolt', 'yango', 'taxi', 'ride', 'fly'
    ] 
  },
];

export const PRODUCTS = [
  {
    id: 'netflix',
    name: 'Netflix',
    description: 'Profitez de millions de films et séries en streaming',
    image: 'https://assets.nflxext.com/us/email/gem/100/netflix-gift-card.png',
    logo: 'https://upload.wikimedia.org/wikipedia/commons/0/08/Netflix_2015_logo.svg',
    categories: [1],
    values: [15, 25, 50, 100],
    currency: 'EUR',
    discount: '2% off',
    startPrice: 15,
    color: '#000000',
    textColor: '#E50914'
  },
  {
    id: 'spotify',
    name: 'Spotify',
    description: 'Écoutez vos musiques préférées sans publicité',
    image: 'https://storage.googleapis.com/pr-newsroom-wp/1/2018/11/Spotify_Logo_CMYK_Green.png',
    logo: 'https://storage.googleapis.com/pr-newsroom-wp/1/2018/11/Spotify_Logo_CMYK_Green.png',
    categories: [3],
    values: [10, 30, 60],
    currency: 'EUR',
    discount: '1.5% off',
    startPrice: 10,
    color: '#1DB954',
    textColor: '#FFFFFF'
  },
  {
    id: 'apple',
    name: 'Apple Store',
    description: 'Apps, jeux, musique et plus encore',
    image: 'https://upload.wikimedia.org/wikipedia/commons/f/fa/Apple_logo_black.svg',
    logo: 'https://upload.wikimedia.org/wikipedia/commons/f/fa/Apple_logo_black.svg',
    categories: [3, 4],
    values: [15, 25, 50, 100],
    currency: 'EUR',
    discount: '5% off',
    startPrice: 15,
    color: '#000000',
    textColor: '#FFFFFF'
  },
  {
    id: 'amazon',
    name: 'Amazon Shopping Voucher',
    description: 'Achetez tout ce que vous voulez sur Amazon',
    image: 'https://upload.wikimedia.org/wikipedia/commons/a/a9/Amazon_logo.svg',
    logo: 'https://upload.wikimedia.org/wikipedia/commons/a/a9/Amazon_logo.svg',
    categories: [4],
    values: [20, 50, 100],
    currency: 'EUR',
    discount: '2% off',
    startPrice: 20,
    color: '#FF9900',
    textColor: '#000000'
  }
];
