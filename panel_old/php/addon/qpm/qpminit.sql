DROP TABLE qpm;
CREATE TABLE qpm(num text PRIMARY KEY, cname text default '', cat text default '', pname text default '', zip text default '', addr text default '', pn1 text default '', pn2 text default '', pn3 text default '', pn4 text default '', memo1 text default '', memo2 text default '', fpfx text default '', attend text default '', last text default '');
DROP TABLE qpm_users;
CREATE TABLE qpm_users(login text PRIMARY KEY, password text default '', dname text default '', ext text default '');
DROP TABLE qpm_cats;
CREATE TABLE qpm_cats(cat text PRIMARY KEY);
