/**
 *@file CSql.hpp
 * @author Fabio Rossini Sluzala ()
 * @brief CSql sql helper header
 * @version 0.1
 *
 * @copyright Copyright (c) 2021
 *
 */
#pragma once
#ifndef CSql_hpp
#define CSql_hpp

#include "GenericDBConnection.hpp"
#include <cppconn/driver.h>
#include <cppconn/exception.h>
#include <cppconn/prepared_statement.h>
#include <cppconn/resultset.h>
#include <cppconn/statement.h>
#include <mysql_connection.h>
#include <mysql_driver.h>

#include <chrono>
#include <memory>
#include <mutex>
#include <optional>

typedef std::unique_ptr<sql::Connection> unique_conn_t;
typedef std::shared_ptr<GenericDBConnection> shared_conn_t;
using unique_statement_t = std::unique_ptr<sql::Statement>;
using unique_prepstatement_t = std::unique_ptr<sql::PreparedStatement>;
using unique_resultset_t = std::unique_ptr<sql::ResultSet>;

class CSql {
    std::mutex sqldrvmtx;

    CSql() = default;
    CSql(const CSql &) = delete;

    auto get_sql_drv() -> sql::mysql::MySQL_Driver *;

  public:
    static inline auto
    high_precision_time_to_str(std::chrono::high_resolution_clock::time_point t)
        -> std::string {
        std::time_t tt = std::chrono::high_resolution_clock::to_time_t(t);

        auto mksec = std::chrono::duration_cast<std::chrono::microseconds>(
                         t.time_since_epoch())
                         .count();
        mksec %= 1000000;

        std::string str;

        {
            std::array<char, 32> buf{};

            size_t strft_res_sz =
                strftime(buf.data(), buf.size(), "%Y/%m/%d %H:%M:%S.",
                         std::localtime(&tt));

            str.reserve(28);
            str.append(buf.data(), strft_res_sz);
        }

        {
            std::string mksecstr = std::to_string(mksec);
            size_t mksecsz = mksecstr.size();

            if (mksecsz < 6) {
                {
                    str.append(6 - mksecsz, '0');
                }
            }

            str += mksecstr;
        }

        return str;
    }

    static inline auto
    system_time_to_str(std::optional<std::chrono::system_clock::time_point> t)
        -> std::string {
        if (!t.has_value())
        {
            return std::string();
        }

        std::time_t tt = std::chrono::system_clock::to_time_t(t.value());

        std::string str;

        {
            std::array<char, 32> buf{};

            size_t strft_res_sz =
                strftime(buf.data(), buf.size(), "%Y/%m/%d %H:%M:%S",
                         std::localtime(&tt));

            str.reserve(28);
            str.append(buf.data(), strft_res_sz);
        }

        return str;
    }

    // Singleton
    static auto instance() -> CSql &;

    auto make_shr_connection_cfg() -> shared_conn_t;
    auto make_connection_cfg() -> unique_conn_t;

    static auto string_to_system_clock(const std::string &str)
        -> std::optional<std::chrono::system_clock::time_point>;

    static inline auto mysql_cast(sql::Connection *conn)
        -> sql::mysql::MySQL_Connection * {
        sql::mysql::MySQL_Connection *mconn =
            dynamic_cast<sql::mysql::MySQL_Connection *>(conn);

        if (mconn == nullptr) {
            throw std::bad_cast();
        }

        return mconn;
    }

    static inline auto escape(sql::mysql::MySQL_Connection *conn,
                              const std::string &val,
                              bool null_if_empty = false) -> std::string {
        if (conn == nullptr) {
            {
                throw std::invalid_argument("Resource is null");
            }
        }

        if (null_if_empty && val.empty()) {
            return "NULL";
        }

        return conn->escapeString(val);
    }

    /**
     *@brief Performs escape of special characters in string to use in sql
     *queries
     *
     * @param conn the mysql connection pointer
     * @param val string value to escape
     * @param null_if_empty return NULL if the string is empty
     * @return std::string
     */
    static inline auto esc_add_q(sql::mysql::MySQL_Connection *conn,
                                 const std::string &val,
                                 bool null_if_empty = false) -> std::string {
        if (conn == nullptr) {
            {
                throw std::invalid_argument("Resource is null");
            }
        }

        if (null_if_empty && val.empty()) {
            return "NULL";
        }

        std::string result;
        result.reserve(val.size() + 2);

        result = "'";
        result += conn->escapeString(val);
        result += "'";

        return result;
    }

    static inline auto
    escape_add_qwith_unhex(sql::mysql::MySQL_Connection *conn,
                           const std::string &val, bool null_if_empty = false)
        -> std::string {
        if (conn == nullptr) {
            {
                throw std::invalid_argument("Resource is null");
            }
        }

        if (null_if_empty && val.empty()) {
            return "NULL";
        }

        std::string result;
        result.reserve(val.size() + 9);

        result = "UNHEX('";
        result += conn->escapeString(val);
        result += "')";

        return result;
    }
};

#endif
