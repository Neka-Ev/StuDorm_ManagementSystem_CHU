---- 1. 创建登录名
IF NOT EXISTS (SELECT name FROM master.sys.server_principals WHERE name = 'dormdb_user')
BEGIN
    CREATE LOGIN dormdb_user 
    WITH PASSWORD = 'P88888888', 
    CHECK_POLICY = ON,
    CHECK_EXPIRATION = ON;
    
    PRINT '登录名 dormdb_user 创建成功';
END
ELSE
BEGIN
    PRINT '登录名 dormdb_user 已存在';
END
GO

---- 2. 创建用户并映射到登录名
USE DormDB;
GO

IF NOT EXISTS (SELECT name FROM sys.database_principals WHERE name = 'dorm_user')
BEGIN
    CREATE USER dorm_user FOR LOGIN dormdb_user;
    PRINT '用户名 dorm_user 创建成功';
END
ELSE
BEGIN
    PRINT '用户名 dorm_user 已存在';
END
GO

USE DormDB

---- 3. 创建普通管理登录名
IF NOT EXISTS (SELECT name FROM master.sys.server_principals WHERE name = 'dormdb_admin')
BEGIN
    CREATE LOGIN dormdb_user 
    WITH PASSWORD = 'AAA666666', 
    CHECK_POLICY = ON,
    CHECK_EXPIRATION = ON;
    
    PRINT '登录名 dormdb_admin 创建成功';
END
ELSE
BEGIN
    PRINT '登录名 dormdb_admin 已存在';
END
GO

---- 4. 创建用户并映射到普通管理登录名
USE DormDB;
GO

IF NOT EXISTS (SELECT name FROM sys.database_principals WHERE name = 'dorm_admin')
BEGIN
    CREATE USER dorm_user FOR LOGIN dormdb_admin;
    PRINT '用户名 dorm_admin 创建成功';
END
ELSE
BEGIN
    PRINT '用户名 dorm_admin 已存在';
END
GO

--授权超级管理员，拥有所有表与视图的所有权限

GRANT ALL PRIVILEGES ON Colleges TO dormdb_user
GRANT ALL PRIVILEGES ON Allocations TO dormdb_user
GRANT ALL PRIVILEGES ON CollegeDormitoryBinding TO dormdb_user
GRANT ALL PRIVILEGES ON DormitoryBuildings TO dormdb_user
GRANT ALL PRIVILEGES ON Students TO dormdb_user
GRANT ALL PRIVILEGES ON DormitoryRooms TO dormdb_user
GRANT ALL PRIVILEGES ON Administrators TO dormdb_user
GRANT ALL PRIVILEGES ON Transfers TO dormdb_user
GRANT ALL PRIVILEGES ON DormRoomOccupancyPivotView TO dormdb_user

--授权普通管理员，无增与删的权限

GRANT ALL PRIVILEGES ON Colleges TO dormdb_admin
GRANT ALL PRIVILEGES ON Allocations TO dormdb_admin
GRANT ALL PRIVILEGES ON CollegeDormitoryBinding TO dormdb_admin
GRANT ALL PRIVILEGES ON DormitoryBuildings TO dormdb_admin
GRANT SELECT, UPDATE ON Students TO dormdb_admin
GRANT ALL PRIVILEGES ON DormitoryRooms TO dormdb_admin
GRANT ALL PRIVILEGES ON Administrators TO dormdb_admin
GRANT ALL PRIVILEGES ON Transfers TO dormdb_admin
GRANT ALL PRIVILEGES ON DormRoomOccupancyPivotView TO dormdb_admin