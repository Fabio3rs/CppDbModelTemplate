#pragma once

#include "../Database/GenericDBConnection.hpp"
#include <chrono>
#include <memory>
#include <optional>

class <?= $MODEL_NAME ?> {
    std::shared_ptr<GenericDBConnection> usingconn;

    auto update() -> int;
    void create();

public:
    /* Model fields */
    uint64_t id{0};
<?php
    foreach ($FIELDS as $k => $v)
    {
        echo '    ', nullablefieldtype($v), ' ', $v->name, extra_attribute_field($v->type), ';', "\n";
    }
    ?>

    /* Functions */
    auto empty() const -> bool { return id == 0; }

    auto findByID(uint64_t fid) -> bool;
    void save();

    <?= $MODEL_NAME ?>(std::shared_ptr<GenericDBConnection> conn)
        : usingconn(conn) {}
};
