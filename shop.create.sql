CREATE TABLE USERS (
    id SERIAL primary key,
    name text not null,
    surname text not null,
    mail text unique,
    password text not null
);

CREATE TABLE TOKENS (
    id INT primary key references USERS,
    cookie_token text not null
);

CREATE TABLE PRODUCT_GROUPS (
    id SERIAL primary key,
    price NUMERIC(7, 2) not null,
    category_name text not null,
    product_name text not null,
    description text not null
);

CREATE TABLE ORDERS (
    id SERIAL primary key,
    user_id INT references USERS,
    order_placed_date date not null,
    order_finished_date date,
    is_finished BOOLEAN DEFAULT FALSE
);

CREATE TABLE PRODUCTS (
    id SERIAL primary key,
    group_id INT references PRODUCT_GROUPS,
    order_id INT DEFAULT NULL references ORDERS,
    is_available BOOLEAN DEFAULT TRUE
);

CREATE TABLE CART (
    id INT primary key references PRODUCTS,
    user_id INT references USERS
);

CREATE TABLE STAFF (
    id INT primary key references USERS
);