# 📰 News Aggregator API

A robust and scalable RESTful API for aggregating news articles from multiple sources with personalized user feeds. Built with Laravel 11, following SOLID principles and modern software architecture patterns.

[![Laravel](https://img.shields.io/badge/Laravel-11-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

---

## 📋 Table of Contents

- [Features](#-features)
- [Architecture](#-architecture)
- [Tech Stack](#-tech-stack)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Database Setup](#-database-setup)
- [Usage](#-usage)
- [API Documentation](#-api-documentation)
- [Testing](#-testing)
- [Project Structure](#-project-structure)
- [Development](#-development)
- [Performance Optimization](#-performance-optimization)
- [Contributing](#-contributing)
- [License](#-license)

---

## ✨ Features

### Core Features
- 🔄 **Multi-Source Aggregation** - Fetch articles from The Guardian, NewsAPI, and New York Times
- 🔍 **Advanced Search** - Full-text search with MySQL full-text indexing
- 🎯 **Personalized Feed** - User-specific article recommendations based on preferences
- 🏷️ **Smart Filtering** - Filter by sources, categories, authors, and date ranges
- 📱 **RESTful API** - Clean, documented, and versioned API endpoints
- 🔐 **Authentication** - Secure token-based auth with Laravel Sanctum
- 📊 **API Documentation** - Interactive Swagger/OpenAPI documentation

### Advanced Features
- ⚡ **Performance Optimization** - Database indexes and query optimization
- 💾 **Caching Strategy** - Multi-tier caching with tag-based invalidation
- 🎨 **API Resources** - Consistent JSON response formatting
- ✅ **Request Validation** - FormRequest validation for all endpoints
- 🔄 **Batch Processing** - Efficient article upsert with deduplication
- 🏗️ **Clean Architecture** - Repository pattern, service layer, SOLID principles
- 📈 **Scalable Design** - Modular architecture ready for horizontal scaling

---

## 🏛️ Architecture

The application follows a **layered architecture** with clear separation of concerns:

```
┌───────────────────────────────────────────────────────────┐
│                   HTTP Layer (Controllers)                │
│  - Handle HTTP requests/responses                         │
│  - Validate input (FormRequests)                          │
│  - Transform output (API Resources)                       │
└───────────────────────────────────────────────────────────┘
                            ↓
┌───────────────────────────────────────────────────────────┐
│              Business Logic Layer (Services)              │
│  - ArticleService (validation, deduplication)             │
│  - PersonalizedFeedService (feed orchestration)           │
│  - UserPreferenceFilterBuilder (filter building)          │
│  - CategoryService (category management)                  │
│  - CacheService (caching strategy)                        │
└───────────────────────────────────────────────────────────┘
                            ↓
┌───────────────────────────────────────────────────────────┐
│           Data Access Layer (Repositories)                │
│  - ArticleRepository (database operations)                │
│  - Query optimization & caching                           │
└───────────────────────────────────────────────────────────┘
                            ↓
┌───────────────────────────────────────────────────────────┐
│                Integration Layer (Adapters)               │
│  - GuardianAdapter (The Guardian API)                     │
│  - NewsApiAdapter (NewsAPI integration)                   │
│  - NytAdapter (New York Times API)                        │
└───────────────────────────────────────────────────────────┘
```

### Design Patterns
- **Repository Pattern** - Abstract data access logic
- **Adapter Pattern** - Integrate different news APIs with unified interface
- **Service Layer** - Encapsulate business logic
- **Dependency Injection** - Loose coupling via interfaces
- **DTO Pattern** - Type-safe data transfer
- **Strategy Pattern** - Flexible filter building

### SOLID Principles
- ✅ **Single Responsibility** - Each class has one reason to change
- ✅ **Open/Closed** - Open for extension, closed for modification
- ✅ **Liskov Substitution** - Interface-based implementations
- ✅ **Interface Segregation** - Small, focused interfaces
- ✅ **Dependency Inversion** - Depend on abstractions, not concretions

---

## 🛠️ Tech Stack

### Backend
- **Framework:** Laravel 11
- **PHP:** 8.2+
- **Database:** SQLite (development) / MySQL (production)
- **Authentication:** Laravel Sanctum
- **API Documentation:** L5-Swagger (OpenAPI 3.0)

### Key Packages
- `guzzlehttp/guzzle` - HTTP client for external APIs
- `darkaonline/l5-swagger` - API documentation
- `laravel/sanctum` - API authentication

### Development Tools
- **Code Style:** Laravel Pint
- **Testing:** PHPUnit
- **Dependency Management:** Composer

---

## 📦 Installation

### Prerequisites
- PHP >= 8.2
- Composer
- SQLite or MySQL
- Git

### Step 1: Clone Repository
```bash
git clone https://github.com/yourusername/innoscripta.git
cd innoscripta
```

### Step 2: Install Dependencies
```bash
composer install
```

### Step 3: Environment Setup
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### Step 4: Configure Environment
Edit `.env` file with your settings:
```env
APP_NAME="News Aggregator"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://news-aggregator.test

DB_CONNECTION=sqlite
# DB_CONNECTION=mysql (for production)

# News API Keys
GUARDIAN_API_KEY=your_guardian_api_key
GUARDIAN_BASE_URL=https://content.guardianapis.com

NEWSAPI_KEY=your_newsapi_key
NEWSAPI_BASE_URL=https://newsapi.org/v2

NYTIMES_API_KEY=your_nytimes_api_key
NYTIMES_BASE_URL=https://api.nytimes.com/svc

# Cache Configuration
CACHE_DRIVER=file
# CACHE_DRIVER=redis (for production)
```

---

## ⚙️ Configuration

### Get API Keys

1. **The Guardian** - [Get API Key](https://open-platform.theguardian.com/access/)
2. **NewsAPI** - [Get API Key](https://newsapi.org/register)
3. **New York Times** - [Get API Key](https://developer.nytimes.com/get-started)

### Cache Configuration

For production, use Redis:
```bash
# Install Redis (macOS)
brew install redis
brew services start redis

# Update .env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

---

## 🗄️ Database Setup

### Step 1: Run Migrations
```bash
php artisan migrate
```

This creates:
- `users` - User accounts
- `sources` - News sources (Guardian, NewsAPI, NYT)
- `categories` - Article categories
- `articles` - News articles with full-text indexes
- `user_preferences` - User preference settings
- `user_preferred_sources` - User's preferred news sources
- `user_preferred_categories` - User's preferred categories
- `user_preferred_authors` - User's preferred authors
- `personal_access_tokens` - Sanctum authentication tokens

### Step 2: Seed Initial Data
```bash
# Seed news sources
php artisan db:seed --class=SourcesSeeder
```

### Step 3: Fetch Articles
```bash
# Fetch articles from all sources
php artisan news:fetch

# Fetch from specific source
php artisan news:fetch --source=guardian
```

### Step 4: Seed User Preferences (Optional)
```bash
# After fetching articles, seed test users with preferences
php artisan db:seed --class=UserPreferencesSeeder
```

This creates test users:
- `tech@example.com` (password: `password`) - Tech enthusiast
- `politics@example.com` (password: `password`) - Politics follower
- `business@example.com` (password: `password`) - Business reader
- `general@example.com` (password: `password`) - General reader

---

## 🚀 Usage

### Start Development Server
```bash
php artisan serve
```

Server will start at `http://news-aggregator.test`

### Fetch Latest Articles
```bash
# Fetch from all sources
php artisan news:fetch

# Fetch from specific source
php artisan news:fetch --source=guardian
```

### Cache Management
```bash
# Clear specific cache
php artisan cache:clear-app articles
php artisan cache:clear-app sources
php artisan cache:clear-app categories

# Clear all application cache
php artisan cache:clear-app all

# View cache statistics
php artisan cache:stats
```

---

## 📚 API Documentation

### Interactive Documentation
Access Swagger UI at:
```
http://news-aggregator.test/api/documentation
```

Features:
- 🔍 Browse all endpoints
- 🧪 Test endpoints directly in browser
- 🔐 Authenticate with Bearer token
- 📝 View request/response examples
- ✅ See validation rules

### Quick API Overview

#### Authentication Endpoints
```bash
# Login
POST /api/v1/login
Body: { "email": "tech@example.com", "password": "password" }

# Get user info
GET /api/v1/me
Headers: Authorization: Bearer {token}

# Logout
POST /api/v1/logout
Headers: Authorization: Bearer {token}
```

#### Public Endpoints
```bash
# Get articles with filters
GET /api/v1/articles
Parameters:
  - searchTerm: string (search in title, description, content)
  - source[]: array (filter by sources: guardian, newsapi, nyt)
  - category[]: array (filter by categories)
  - author: string
  - from_date: date (YYYY-MM-DD)
  - to_date: date (YYYY-MM-DD)
  - sort: string (published_at, created_at, title)
  - order: string (asc, desc)
  - per_page: integer (1-100)
  - page: integer

# Example
GET /api/v1/articles?searchTerm=technology&source[]=guardian&category[]=Technology&per_page=10
```

#### Authenticated Endpoints
```bash
# Get personalized feed
GET /api/v1/user/feed
Headers: Authorization: Bearer {token}
Parameters: (same as /articles, extends user preferences)

# Example
GET /api/v1/user/feed?searchTerm=climate&per_page=5
```

### Example cURL Commands

**Login:**
```bash
curl -X POST "http://news-aggregator.test/api/v1/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"tech@example.com","password":"password"}'
```

**Get Articles:**
```bash
curl -X GET "http://news-aggregator.test/api/v1/articles?source[]=guardian&category[]=Technology&per_page=10" \
  -H "Accept: application/json"
```

**Get Personalized Feed:**
```bash
curl -X GET "http://news-aggregator.test/api/v1/user/feed" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## 🧪 Testing

### Run Tests
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage
php artisan test --coverage

# Run specific test file
./vendor/bin/phpunit tests/Unit/UserPreferenceFilterBuilderTest.php --testdox
```

### Unit Tests Coverage

#### `UserPreferenceFilterBuilderTest` ✅
Comprehensive tests for filter building logic (11 tests, 55 assertions):
- ✅ Building filters from user preferences only
- ✅ Building filters with empty preferences
- ✅ Overriding preferences with request parameters
- ✅ Expanding source filters by merging with request
- ✅ Expanding category filters by merging with request
- ✅ Removing duplicate sources when merging
- ✅ Normalizing single value input to array
- ✅ Adding date range filters from request
- ✅ Complete scenario with all filter types
- ✅ Handling null request

**Test File:** `tests/Unit/UserPreferenceFilterBuilderTest.php`

---

#### `ArticleServiceTest` ✅
Comprehensive tests for article business logic (16 tests, 51 assertions):
- ✅ Processing empty articles array
- ✅ Removing duplicates (keeps last occurrence)
- ✅ Rejecting articles with missing required fields
- ✅ Rejecting articles with invalid URL
- ✅ Rejecting articles with title too long
- ✅ Accepting articles with max length title (255 chars)
- ✅ Processing valid articles calls repository
- ✅ Cache invalidation when articles inserted
- ✅ Cache invalidation when articles updated
- ✅ No cache invalidation when no changes
- ✅ Multiple validation errors tracking
- ✅ Articles without merchant_id removed during deduplication
- ✅ Complete flow with mixed articles (valid, invalid, duplicates)
- ✅ Repository failure propagation
- ✅ Optional fields can be empty
- ✅ Valid URL formats acceptance (HTTP, HTTPS, with paths)

**Test File:** `tests/Unit/ArticleServiceTest.php`

---

#### `NewsAggregatorServiceTest` ✅
Comprehensive tests for news fetching orchestration (11 tests, 92 assertions):
- ✅ Fetching from all sources successfully
- ✅ Fetching from single source successfully
- ✅ Handling empty article responses
- ✅ Exception handling during fetch
- ✅ Mixed results (inserted, updated, failed, skipped)
- ✅ DTO to array conversion
- ✅ Continuing when one source fails
- ✅ Response structure validation
- ✅ Error response structure
- ✅ No sources configured
- ✅ Default skipped count handling

**Test File:** `tests/Unit/NewsAggregatorServiceTest.php`

### Manual Testing with Postman
1. Import the OpenAPI spec from `storage/api-docs/api-docs.json`
2. Or use the Swagger UI at `http://news-aggregator.test/api/documentation`

### Test Users (after seeding)
- Email: `tech@example.com` | Password: `password`
- Email: `politics@example.com` | Password: `password`
- Email: `business@example.com` | Password: `password`
- Email: `general@example.com` | Password: `password`

---

## 📁 Project Structure

```
innoscripta/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       ├── FetchArticlesCommand.php      # Artisan command to fetch articles
│   │       ├── CacheClearCommand.php         # Cache management
│   │       └── CacheStatsCommand.php         # Cache statistics
│   │
│   ├── Contracts/                            # Interface contracts
│   │   ├── ArticleRepositoryInterface.php
│   │   ├── NewsSourceInterface.php
│   │   └── PersonalizedFeedServiceInterface.php
│   │
│   ├── DTO/
│   │   └── ArticleDTO.php                    # Data Transfer Object
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       ├── ArticleController.php     # Public articles endpoint
│   │   │       ├── AuthController.php        # Authentication
│   │   │       └── UserFeedController.php    # Personalized feed
│   │   │
│   │   ├── Requests/
│   │   │   └── Api/
│   │   │       └── ArticleIndexRequest.php   # Validation rules
│   │   │
│   │   └── Resources/
│   │       ├── ArticleResource.php           # Article JSON transformation
│   │       ├── SourceResource.php
│   │       └── CategoryResource.php
│   │
│   ├── Models/
│   │   ├── Article.php                       # Article model with scopes
│   │   ├── Category.php
│   │   ├── Source.php
│   │   ├── User.php
│   │   └── UserPreference.php
│   │
│   ├── Repositories/
│   │   └── ArticleRepository.php             # Database operations
│   │
│   ├── Services/
│   │   ├── Adapters/                         # News API adapters
│   │   │   ├── GuardianAdapter.php
│   │   │   ├── NewsApiAdapter.php
│   │   │   └── NytAdapter.php
│   │   │
│   │   ├── ArticleService.php                # Article business logic
│   │   ├── CacheService.php                  # Caching strategy
│   │   ├── CategoryService.php               # Category management
│   │   ├── HttpClientService.php             # HTTP requests
│   │   ├── NewsAggregatorService.php         # Orchestrates fetching
│   │   ├── PersonalizedFeedService.php       # Personalized feed
│   │   └── UserPreferenceFilterBuilder.php   # Filter building
│   │
│   └── Utils/
│       └── DateTimeHelper.php                # DateTime utilities
│
├── config/
│   ├── news.php                              # News sources configuration
│   └── l5-swagger.php                        # API documentation config
│
├── database/
│   ├── migrations/                           # Database schema
│   └── seeders/
│       ├── SourcesSeeder.php                 # Seeds news sources
│       └── UserPreferencesSeeder.php         # Seeds test users
│
├── routes/
│   └── api.php                               # API routes
│
└── storage/
    └── api-docs/
        └── api-docs.json                     # OpenAPI specification
```

---

## 💻 Development

### Code Style
```bash
# Format code with Laravel Pint
./vendor/bin/pint
```

### Generate API Documentation
```bash
# After modifying API annotations
php artisan l5-swagger:generate
```

### Clear All Caches
```bash
# Clear application cache
php artisan cache:clear

# Clear configuration cache
php artisan config:clear

# Clear route cache
php artisan route:clear

# Clear view cache
php artisan view:clear
```

### Debugging
```bash
# View routes
php artisan route:list

# View database queries
# Add to .env: DB_LOG_QUERIES=true

# Tail logs
tail -f storage/logs/laravel.log
```

---

## ⚡ Performance Optimization

### Database Indexes
The application uses strategic indexes for optimal query performance:

```sql
-- Single column indexes
INDEX on articles.published_at

-- Composite indexes
INDEX on (source_id, published_at)
INDEX on (category_id, published_at)

-- Full-text indexes (MySQL)
FULLTEXT INDEX on (title, description, content)
```

### Caching Strategy
Multi-tier caching with different TTLs:

| Cache Type | TTL | Invalidation |
|------------|-----|--------------|
| Query Results | 30 min | Tag-based |
| User Feed | 15 min | Tag-based |
| Metadata (sources/categories) | 1 hour | Tag-based |
| Articles | 2 hours | Tag-based |

**Cache Commands:**
```bash
# Clear specific caches
php artisan cache:clear-app articles
php artisan cache:clear-app sources
php artisan cache:clear-app categories
php artisan cache:clear-app metadata

# Clear all
php artisan cache:clear-app all
```

### Query Optimization
- Eager loading relationships (`with()`)
- Query scopes for reusable filters
- Batch upserts for article storage
- Pagination for large result sets

---

## 🔄 Scheduled Tasks (Optional)

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Fetch articles every hour
    $schedule->command('news:fetch')
        ->hourly()
        ->withoutOverlapping();
    
    // Clear old cache daily
    $schedule->command('cache:clear-app all')
        ->daily();
}
```

Run scheduler:
```bash
php artisan schedule:work
```

---

## 🎯 Best Practices Implemented

### Code Quality
- ✅ SOLID principles adherence
- ✅ Repository pattern for data access
- ✅ Service layer for business logic
- ✅ Dependency injection throughout
- ✅ Type-safe with PHP 8.2+ features
- ✅ Comprehensive PHPDoc comments

### Security
- ✅ Token-based authentication
- ✅ Request validation with FormRequests
- ✅ SQL injection prevention (Eloquent ORM)
- ✅ Rate limiting on API routes
- ✅ Environment variable configuration

### Performance
- ✅ Database query optimization
- ✅ Strategic indexes
- ✅ Multi-tier caching
- ✅ Batch processing
- ✅ Lazy loading where appropriate

### Maintainability
- ✅ Clear directory structure
- ✅ Separation of concerns
- ✅ Interface-based design
- ✅ Comprehensive documentation
- ✅ Consistent naming conventions

---

## 🤝 Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

### Code Standards
- Follow PSR-12 coding standards
- Write PHPDoc comments for all methods
- Add tests for new features
- Update API documentation (Swagger annotations)
- Run Laravel Pint before committing

---

## 🐛 Troubleshooting

### Common Issues

**Issue: "Class not found" errors**
```bash
composer dump-autoload
```

**Issue: Cache not clearing**
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

**Issue: API not fetching articles**
- Check your API keys in `.env`
- Verify API rate limits
- Check logs: `storage/logs/laravel.log`

**Issue: Swagger UI not showing**
```bash
php artisan l5-swagger:generate
php artisan route:clear
```

**Issue: Database connection error**
- Check `DB_CONNECTION` in `.env`
- Verify SQLite file exists: `database/database.sqlite`
- For MySQL, check credentials and server status

---

## 📄 License

This project is open-sourced software licensed under the [MIT license](LICENSE).

---

## 👥 Authors

- **Your Name** - *Initial work* - [Your GitHub](https://github.com/yourusername)

---

## 🙏 Acknowledgments

- The Guardian API for news data
- NewsAPI for aggregated news sources
- New York Times API for quality journalism
- Laravel community for excellent documentation
- Open source contributors

---

## 📞 Support

For support, email support@newsaggregator.com or open an issue on GitHub.

---

## 🗺️ Roadmap

### Phase 1: Core Features ✅
- [x] Multi-source news aggregation
- [x] Article search and filtering
- [x] User authentication
- [x] Personalized feeds
- [x] API documentation

### Phase 2: Enhancements (Planned)
- [ ] Bookmarking articles
- [ ] Article recommendations (ML-based)
- [ ] Email digest notifications
- [ ] Advanced analytics dashboard
- [ ] Social sharing features

### Phase 3: Scaling (Future)
- [ ] Laravel Scout (Elasticsearch) integration
- [ ] Redis caching in production
- [ ] Horizontal scaling with load balancers
- [ ] CDN for media assets
- [ ] GraphQL API endpoint

---

## 📊 Statistics

- **Total Endpoints:** 5
- **News Sources:** 3 (The Guardian, NewsAPI, New York Times)
- **Database Tables:** 10
- **Service Classes:** 8
- **Repositories:** 1
- **API Resources:** 3
- **Lines of Code:** ~5,000+

---

**Built with ❤️ using Laravel 11**
