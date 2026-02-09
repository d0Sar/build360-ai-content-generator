// Sample data for the products
const productsData = [
  {
    id: 1,
    name: "Wireless Bluetooth Headphones",
    category: "Electronics",
    status: "generated",
    date: "2023-11-15",
    features: [
      "40-hour battery life",
      "Active noise cancellation",
      "Comfortable over-ear design",
      "High-quality sound with deep bass"
    ],
    description: "Experience premium sound quality with our Wireless Bluetooth Headphones. Featuring industry-leading active noise cancellation technology, these headphones create an immersive audio experience by blocking out ambient noise so you can focus on your music. With up to 40 hours of battery life, you can enjoy your favorite tunes all day long without worrying about recharging. The comfortable over-ear design with soft cushions makes them perfect for extended listening sessions. Deep, rich bass response and crystal-clear highs deliver exceptional sound performance for all music genres. Connect effortlessly to your devices via Bluetooth 5.0 for stable, lag-free audio streaming. Perfect for work, travel, or everyday use."
  },
  {
    id: 2,
    name: "Ultra HD Smart TV 55\"",
    category: "Electronics",
    status: "generated",
    date: "2023-11-16",
    features: [
      "55-inch 4K UHD display",
      "Smart TV with built-in streaming apps",
      "Voice control capability",
      "Multiple HDMI and USB ports"
    ],
    description: "Transform your home entertainment with our 55\" Ultra HD Smart TV. This stunning television delivers breathtaking 4K UHD resolution, bringing your favorite movies and shows to life with incredible detail and vibrant colors. The built-in smart platform gives you instant access to popular streaming services like Netflix, Hulu, and Disney+ without the need for external devices. Control your TV effortlessly using just your voice with the integrated voice assistant compatibility. With multiple HDMI and USB ports, you can easily connect all your devices, from gaming consoles to sound systems. The sleek, borderless design complements any room décor while maximizing screen space. Experience the perfect combination of cutting-edge technology and user-friendly features."
  },
  {
    id: 3,
    name: "Professional Chef's Knife",
    category: "Home & Garden",
    status: "pending",
    date: "2023-11-17",
    features: [
      "8-inch high-carbon stainless steel blade",
      "Ergonomic handle for comfortable grip",
      "Full tang construction for balance",
      "Precision-forged for durability"
    ],
    description: ""
  },
  {
    id: 4,
    name: "Organic Moisturizing Face Cream",
    category: "Beauty & Personal Care",
    status: "pending",
    date: "2023-11-18",
    features: [
      "Made with 100% organic ingredients",
      "Deeply hydrates and nourishes skin",
      "Non-greasy formula",
      "Suitable for sensitive skin"
    ],
    description: ""
  },
  {
    id: 5,
    name: "Athletic Running Shoes",
    category: "Clothing",
    status: "pending",
    date: "2023-11-19",
    features: [
      "Lightweight, breathable mesh upper",
      "Responsive cushioning technology",
      "Durable rubber outsole",
      "Reflective elements for night visibility"
    ],
    description: ""
  }
];

// Settings data
const settingsData = {
  apiKey: "",
  domainName: "example.com",
  model: "gpt-3.5-turbo",
  textStyle: "professional",
  maxCharsProducts: "250",
  maxCharsDescriptions: "250",
  maxCharsBlog: "250",
  contentTypes: {
    image: {
      enabled: true,
      options: {
        "name": true,
        "alt-text": true,
        "description": true,
        "caption": true
      }
    },
    product: {
      enabled: true,
      options: {
        "name": true,
        "description": true,
        "short-description": true,
        "meta-description": true,
        "image-alt": true
      }
    },
    "product-category": {
      enabled: true,
      options: {
        "name": true,
        "description": true,
        "short-description": true,
        "seo-keyword": true,
        "seo-title": true,
        "seo-meta-description": true,
        "image-alt": true
      }
    },
    post: {
      enabled: true,
      options: {
        "name": true,
        "description": true,
        "seo-keyword": true,
        "seo-title": true,
        "seo-meta-description": true,
        "image-alt": true
      }
    },
    page: {
      enabled: true,
      options: {
        "name": true,
        "description": true,
        "seo-title": true,
        "seo-meta-description": true
      }
    }
  }
};

// Categories
const categories = [
  { value: "electronics", label: "Electronics" },
  { value: "clothing", label: "Clothing" },
  { value: "home", label: "Home & Garden" },
  { value: "beauty", label: "Beauty & Personal Care" }
];

// Tones
const tones = [
  { value: "professional", label: "Professional" },
  { value: "casual", label: "Casual" },
  { value: "enthusiastic", label: "Enthusiastic" },
  { value: "formal", label: "Formal" }
];

// Languages
const languages = [
  { value: "en", label: "English" },
  { value: "el", label: "Ελληνικά" },
  { value: "es", label: "Spanish" },
  { value: "fr", label: "French" },
  { value: "de", label: "German" }
];