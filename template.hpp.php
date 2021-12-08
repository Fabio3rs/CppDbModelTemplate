#pragma once

#include <Poco/JSON/JSON.h>
#include <Poco/Dynamic/Var.h>
#include <Poco/JSON/Object.h>

#include "../Database/GenericDBConnection.hpp"
#include <chrono>
#include <memory>
#include <optional>
#include <string>

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
        echo '    ', nullablefieldtype($v), ' ', $v->name, extra_attribute_field($v), ';', "\n";
    }
    ?>

    /* Functions */
    auto empty() const -> bool { return id == 0; }

<?= declare_findable($FIELDS, $FINDABLES) ?>

    auto findByID(uint64_t fid) -> bool;
    void save();

    auto dump_json() -> Poco::JSON::Object::Ptr;

    <?= $MODEL_NAME ?>(std::shared_ptr<GenericDBConnection> conn)
        : usingconn(conn) {}
};
