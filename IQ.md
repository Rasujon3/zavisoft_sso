# Sherazi POS Project — Interview প্রশ্ন ও উত্তর

---

## ১. N+1 Query সমস্যা

**প্রশ্ন: N+1 Query সমস্যা কী এবং এই project-এ কোথায় ছিল?**

উত্তর: N+1 মানে 1টা main query চালানোর পর প্রতিটা result-এর জন্য আলাদা আলাদা query চালানো। এই project-এ `ProductController::index()` তে `Product::all()` দিয়ে 500টা product আনার পর loop-এ `$product->category->name` access করলে 500টা আলাদা query fire হতো। মোট 501টা query। একইভাবে `OrderController::index()` তে customer আর items access করায় 600+ query হতো।

---

**প্রশ্ন: Eager Loading দিয়ে কীভাবে fix করলে?**

উত্তর: `with()` method ব্যবহার করে। `Product::with('category')->paginate(15)` করলে Laravel প্রথমে products আনে, তারপর একটাই query দিয়ে সব products-এর category এক সাথে এনে PHP memory-তে match করে দেয়। 501 query থেকে 2 query-তে নেমে আসে।

---

**প্রশ্ন: `salesReport()` তে N+1 কতটা খারাপ ছিল?**

উত্তর: এটা ছিল nested N+1 — সবচেয়ে খারাপ ধরন। `Order::all()` করে 200টা order আনা, তারপর প্রতিটা order-এর items loop করা, তারপর প্রতিটা item-এর product access করা। 200 orders × গড় 5 items = 1000+ extra query শুধু product আনতে। Fix হয়েছে `Order::with(['customer', 'items.product'])` দিয়ে।

---

## ২. Database Aggregation

**প্রশ্ন: `Product::all()->count()` কেন সমস্যা এবং সঠিক উপায় কী?**

উত্তর: `Product::all()` করলে database-এর সব row PHP-তে Eloquent object হিসেবে load হয়, তারপর PHP সেগুলো count করে। 500 products মানে 500টা object memory-তে। সঠিক উপায় `Product::count()` — এটা শুধু `SELECT COUNT(*) FROM products` চালায়, একটাও object তৈরি হয় না।

---

**প্রশ্ন: Dashboard-এ `Product::all()->sortByDesc('sold_count')->take(5)` কেন ভুল?**

উত্তর: এটা 500টা product PHP-তে load করে, PHP-তে sort করে, তারপর 5টা নেয়। বাকি 495টা object শুধু memory waste করে। সঠিক উপায় `Product::orderByDesc('sold_count')->limit(5)->get()` — sorting আর limiting database-এই হয়, PHP-তে মাত্র 5টা object আসে।

---

## ৩. SQL Injection

**প্রশ্ন: `filterByStatus()` তে SQL Injection vulnerability কোথায় ছিল?**

উত্তর: `DB::select("SELECT * FROM orders WHERE status = '$status'")` — এখানে `$status` সরাসরি query string-এ বসানো হয়েছে। কেউ যদি `?status=' OR '1'='1` পাঠায় তাহলে সব orders বের হয়ে যাবে। আরও খারাপ input দিয়ে data delete বা পুরো database নষ্ট করা সম্ভব।

---

**প্রশ্ন: SQL Injection কীভাবে fix করলে?**

উত্তর: দুইভাবে। প্রথমত validation দিয়ে — `'status' => 'required|in:pending,completed,cancelled'` — এর বাইরের কোনো value এলেই reject। দ্বিতীয়ত Eloquent ব্যবহার করে — `Order::where('status', $status)` — Eloquent internally parameterised query ব্যবহার করে যেখানে value আর query আলাদা থাকে, injection সম্ভব না।

---

## ৪. DB Transaction

**প্রশ্ন: `OrderController::store()` তে Transaction না থাকলে কী সমস্যা হতো?**

উত্তর: ধরো একটা order-এ 5টা item। Order create হয়েছে, 3টা item create হয়েছে, 4র্থটায় error হলো। তাহলে database-এ একটা incomplete order থেকে যাবে — order আছে কিন্তু সব item নেই, total ভুল। Transaction থাকলে যেকোনো error-এ সব কিছু rollback হয়ে যায়, database সবসময় consistent থাকে।

---

**প্রশ্ন: Original code-এ Transaction-এর ভেতরে `response()->json()` return করা হয়েছিল — এটা কেন ভুল?**

উত্তর: Transaction-এর closure থেকে HTTP response object return করলে Laravel সেটাকে normal return হিসেবে নেয়, exception হিসেবে না। ফলে error হলেও rollback trigger হয় না। সঠিক উপায় হলো closure-এর ভেতরে `abort()` বা exception throw করা — এগুলো transaction-কে rollback করতে বলে।

---

## ৫. Redis Cache

**প্রশ্ন: `Cache::remember()` কীভাবে কাজ করে?**

উত্তর: প্রথমে দেওয়া key দিয়ে cache-এ খোঁজে। পেলে সেটা return করে, DB-তে যায় না। না পেলে closure-এর code চালায়, result cache-এ save করে, তারপর return করে। `Cache::remember('key', 300, fn() => ...)` মানে 300 সেকেন্ড অর্থাৎ 5 মিনিট cache থাকবে।

---

**প্রশ্ন: Cache key-এ page number কেন যোগ করলে?**

উত্তর: Pagination-এর প্রতিটা page-এর data আলাদা। শুধু `products:list` key রাখলে page 1-এর data দিয়ে সব page serve হয়ে যেত। `products:list:page:1`, `products:list:page:2` আলাদা key রাখায় প্রতিটা page-এর নিজস্ব cache আছে।

---

**প্রশ্ন: Redis Tags কী এবং এখানে কেন ব্যবহার করলে?**

উত্তর: Tags দিয়ে একাধিক cache key-কে একটা group-এ রাখা যায়। `Cache::tags(['products'])->flush()` করলে products tag-এর সব key একসাথে delete হয়। এখানে 34টা page আছে — আগে loop দিয়ে 50 বার `Cache::forget()` করতে হতো। Tags দিয়ে একটাই call-এ সব clear হয়। Clean, efficient, এবং page count নিয়ে guess করতে হয় না।

---

**প্রশ্ন: Order create হলে products cache-ও কেন clear করলে?**

উত্তর: Order create হলে প্রতিটা product-এর stock কমে। যদি products cache clear না করি তাহলে cached response-এ পুরনো stock দেখাবে। user দেখবে stock 10 আছে কিন্তু আসলে 0। তাই orders আর products — দুটো cache-ই clear করতে হয়।

---

## ৬. API Resources ও Pagination

**প্রশ্ন: Raw `response()->json($array)` এর বদলে API Resource কেন ভালো?**

উত্তর: Resource class আলাদা জায়গায় response structure define করে রাখে। একই Product অনেক endpoint-এ return হলে সব জায়গায় একই format থাকে। কোনো field বদলাতে হলে Resource-এ একবার বদলালেই সব জায়গায় apply হয়। আর `whenLoaded()` দিয়ে relationship না থাকলে সেই field response-এ আসেই না — N+1 সম্ভব না।

---

**প্রশ্ন: `whenLoaded()` কেন গুরুত্বপূর্ণ?**

উত্তর: `$this->category->name` লিখলে category eager load না হলেও Resource lazy load করে নেয় — N+1 ফিরে আসে। `$this->whenLoaded('category')` লিখলে category already load করা না থাকলে সেই field response-এ আসেই না, আলাদা query হয় না। Resource নিরাপদ থাকে।

---

**প্রশ্ন: `paginate(15)` ব্যবহার না করলে কী সমস্যা হতো?**

উত্তর: `Product::all()` দিলে 500টা product একটাই response-এ যেত। Response size বিশাল হতো, client সেটা parse করতে সময় নিত, এবং server-এ memory চাপ পড়ত। Paginate করায় প্রতিবার মাত্র 15টা আসে, response দ্রুত, client-ও সহজে handle করতে পারে।

---

## ৭. Database Indexing

**প্রশ্ন: কোন কোন column-এ index দিলে এবং কেন?**

উত্তর: `orders.status` — `WHERE status = ?` filter-এ ব্যবহার হয়। `products.sold_count` — `ORDER BY sold_count DESC` তে ব্যবহার হয়। `order_items.product_id` — eager load join-এ ব্যবহার হয়। `products.name` — search-এ LIKE ব্যবহার হয়, এখানে `fullText` index আরও ভালো। Index ছাড়া এই queries-গুলো পুরো table scan করত।

---

**প্রশ্ন: LIKE search-এ normal index কাজ করে না কেন?**

উত্তর: `LIKE '%keyword%'` মানে keyword যেকোনো জায়গায় থাকতে পারে। Normal B-tree index শুধু শুরু থেকে match করতে পারে। `%` দিয়ে শুরু হলে index skip হয়ে full table scan হয়। এজন্য `fullText` index দিয়েছি এবং `whereFullText()` ব্যবহার করাটা সঠিক সমাধান।

---

## ৮. Rate Limiting ও Sanctum

**প্রশ্ন: Login route-এ `throttle:10,1` কেন সবচেয়ে কম রাখলে?**

উত্তর: Login endpoint brute force attack-এর সবচেয়ে বড় target। কেউ automated script দিয়ে password guess করার চেষ্টা করলে প্রতি মিনিটে মাত্র 10 বার চেষ্টা করতে পারবে। Public GET routes-এ 100 রেখেছি কারণ normal user-ও বারবার hit করতে পারে এবং সেগুলো cached।

---

**প্রশ্ন: Sanctum Token কীভাবে কাজ করে?**

উত্তর: Login করলে `$user->createToken('pos-app')->plainTextToken` দিয়ে একটা random token তৈরি হয় এবং database-এ hash করে save হয়। Client পরবর্তী request-এ `Authorization: Bearer {token}` header-এ পাঠায়। Laravel সেটা database-এর hash-এর সাথে মিলিয়ে দেখে। মিললে authenticated, না মিললে 401।

---

## ৯. Architectural প্রশ্ন

**প্রশ্ন: এই project 100,000 product-এ scale করতে হলে কী করতে?**

উত্তর: Offset pagination-এর বদলে cursor-based pagination — কারণ `OFFSET 50000` হলে DB 50000 row skip করে scan করে, যেটা slow। Search-এর জন্য Meilisearch বা Elasticsearch — LIKE query-র চেয়ে অনেক দ্রুত। Read replica database — read traffic আলাদা server-এ। আর Redis cache TTL আরও বাড়ানো।

---

**প্রশ্ন: Model Observer বা Event/Listener দিয়ে cache invalidation করলে কী সুবিধা হতো?**

উত্তর: এখন Controller-এ `Cache::tags(['products'])->flush()` লিখতে হচ্ছে। ভবিষ্যতে যদি আরও 5 জায়গা থেকে Product create হয় — Seeder, Queue, Command, Import — সব জায়গায় আলাদা করে লিখতে হবে। Observer বা Listener-এ একবার লিখলে `Product::create()` যেখান থেকেই হোক, automatically trigger হবে। Controller সম্পূর্ণ clean থাকবে।

---

**প্রশ্ন: `DB::transaction()` এর ভেতরে আরেকটা loop-এ `Product::find()` আছে — এটা কি N+1?**

উত্তর: হ্যাঁ, এটা একটা remaining issue। প্রতিটা item-এর জন্য আলাদা `Product::find()` query হচ্ছে। Fix করতে হলে loop-এর আগে সব product_id collect করে `Product::whereIn('id', $productIds)->get()->keyBy('id')` দিয়ে একবারেই সব product আনা উচিত। তারপর loop-এ collection থেকে access করলে আর query হবে না।
